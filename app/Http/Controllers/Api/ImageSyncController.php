<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class ImageSyncController extends Controller
{
    /**
     * Get image synchronization status
     */
    public function getSyncStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'last_synced_at' => 'nullable|numeric'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 422);
        }

        $lastSyncedAt = $request->input('last_synced_at', 0);
        $lastSyncDate = $lastSyncedAt > 0 ? Carbon::createFromTimestamp($lastSyncedAt) : Carbon::createFromTimestamp(0);

        // Get images that need to be uploaded (this would come from the client)
        // In a real scenario, the client would tell the server which images it has

        // Get images that need to be downloaded (images updated since last sync)
        $imagesToDownload = Media::where('model_type', 'LIKE', '%App%Models%')
            ->where('collection_name', 'images')
            ->where('updated_at', '>', $lastSyncDate)
            ->get()
            ->map(function ($media) {
                return [
                    'id' => $media->id,
                    'url' => $media->getFullUrl(),
                    'metadata' => [
                        'file_name' => $media->file_name,
                        'size' => $media->size,
                        'mime_type' => $media->mime_type,
                        'model_type' => $media->model_type,
                        'model_id' => $media->model_id,
                        'created_at' => $media->created_at->timestamp,
                        'updated_at' => $media->updated_at->timestamp,
                    ]
                ];
            });

        return response()->json([
            'imagesToUpload' => [], // This would be determined by the client
            'imagesToDownload' => $imagesToDownload,
            'timestamp' => Carbon::now()->timestamp
        ]);
    }

    /**
     * Upload an image
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|image|max:10240', // Max 10MB
            'associated_entity' => 'required|string',
            'associated_id' => 'required|string',
            'metadata' => 'nullable|json'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Invalid parameters',
                'details' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Find the associated model
            $modelClass = $this->getModelClass($request->input('associated_entity'));
            $model = $modelClass::find($request->input('associated_id'));

            if (!$model) {
                return response()->json([
                    'error' => 'Associated entity not found'
                ], 404);
            }

            // Parse metadata
            $metadata = $request->input('metadata') ? json_decode($request->input('metadata'), true) : [];

            // Add the media
            $media = $model->addMediaFromRequest('file')
                ->withCustomProperties($metadata)
                ->toMediaCollection('images');

            DB::commit();

            return response()->json([
                'id' => $media->id,
                'url' => $media->getFullUrl(),
                'metadata' => [
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                    'created_at' => $media->created_at->timestamp,
                    'updated_at' => $media->updated_at->timestamp,
                ]
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Failed to upload image',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get image by ID
     */
    public function getImage($id): JsonResponse
    {
        $media = Media::find($id);

        if (!$media) {
            return response()->json([
                'error' => 'Image not found'
            ], 404);
        }

        return response()->json([
            'id' => $media->id,
            'url' => $media->getFullUrl(),
            'metadata' => [
                'file_name' => $media->file_name,
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'model_type' => $media->model_type,
                'model_id' => $media->model_id,
                'created_at' => $media->created_at->timestamp,
                'updated_at' => $media->updated_at->timestamp,
                'custom_properties' => $media->custom_properties,
            ]
        ]);
    }

    /**
     * Get images by associated entity
     */
    public function getEntityImages($entityType, $entityId): JsonResponse
    {
        $modelClass = $this->getModelClass($entityType);
        $model = $modelClass::find($entityId);

        if (!$model) {
            return response()->json([
                'error' => 'Entity not found'
            ], 404);
        }

        $images = $model->getMedia('images')->map(function ($media) {
            return [
                'id' => $media->id,
                'url' => $media->getFullUrl(),
                'metadata' => [
                    'file_name' => $media->file_name,
                    'size' => $media->size,
                    'mime_type' => $media->mime_type,
                    'created_at' => $media->created_at->timestamp,
                    'updated_at' => $media->updated_at->timestamp,
                    'custom_properties' => $media->custom_properties,
                ]
            ];
        });

        return response()->json([
            'images' => $images
        ]);
    }

    /**
     * Clean up orphaned images
     */
    public function cleanupOrphanedImages(Request $request): JsonResponse
    {
        // This would typically be run as a scheduled job, but we provide an API endpoint too
        $orphanedImages = [];

        // Get all media items
        $allMedia = Media::where('collection_name', 'images')->get();

        foreach ($allMedia as $media) {
            $modelClass = $media->model_type;

            // Check if the model still exists
            if (!class_exists($modelClass) || !$modelClass::find($media->model_id)) {
                $orphanedImages[] = [
                    'id' => $media->id,
                    'file_name' => $media->file_name,
                    'model_type' => $media->model_type,
                    'model_id' => $media->model_id,
                ];

                // Delete the media (optional)
                // $media->delete();
            }
        }

        return response()->json([
            'orphaned_images' => $orphanedImages,
            'count' => count($orphanedImages)
        ]);
    }

    /**
     * Get model class from entity type string
     */
    private function getModelClass($entityType): string
    {
        // Map your entity types to model classes
        $modelMap = [
            'user' => User::class,
            // Add more mappings as needed
        ];

        return $modelMap[$entityType] ?? 'App\\Models\\' . ucfirst($entityType);
    }
}
