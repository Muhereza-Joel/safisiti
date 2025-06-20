<x-filament-panels::page>
    <div class="space-y-4">

        <!-- Loading State -->
        @if($isLoading)
        <div class="flex justify-center items-center h-64">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-500"></div>
        </div>
        @else
        <!-- Stats Summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Routes</div>
                <div class="text-2xl font-bold">{{ count($routes) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Wards</div>
                <div class="text-2xl font-bold">{{ count($wards) }}</div>
            </div>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-4">
                <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Connections</div>
                <div class="text-2xl font-bold">{{ count($connections) }}</div>
            </div>
        </div>

        <!-- Graph with Floating Panel -->
        <div class="relative bg-white rounded-lg shadow p-4 dark:bg-gray-800">
            <div class="graph-container"
                wire:ignore
                x-data="graphVisualization()"
                x-init="init(@js($connections))">
            </div>


        </div>
        @endif
    </div>

    @push('scripts')
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script>
        function graphVisualization() {
            return {
                init(connections) {
                    const $wire = window.Livewire;
                    const container = this.$el;
                    const width = container.clientWidth;
                    const height = 600;
                    container.innerHTML = '';

                    const svg = d3.select(container)
                        .append('svg')
                        .attr('width', width)
                        .attr('height', height);

                    const routeMap = new Map();
                    const wardMap = new Map();
                    const wardConnectionCount = {};

                    connections.forEach(c => {
                        routeMap.set(c.source, c.route_name);
                        wardMap.set(c.target, c.ward_name);

                        if (!wardConnectionCount[c.target]) {
                            wardConnectionCount[c.target] = new Set();
                        }
                        wardConnectionCount[c.target].add(c.source);
                    });

                    const routeNodes = [...routeMap.keys()].map((id, i) => ({
                        id,
                        type: 'route',
                        name: routeMap.get(id),
                        x: 100,
                        y: 100 + i * 80
                    }));

                    const relayNode = {
                        id: 'relay',
                        type: 'relay',
                        name: 'Routing Hub',
                        x: width / 2,
                        y: height / 2
                    };

                    const wardNodes = [...wardMap.keys()].map((id, i) => ({
                        id,
                        type: 'ward',
                        name: wardMap.get(id),
                        x: width - 100,
                        y: 100 + i * 80
                    }));

                    const nodes = [...routeNodes, relayNode, ...wardNodes];
                    const nodesMap = new Map(nodes.map(n => [n.id, n]));

                    const links = [
                        ...connections.map(conn => ({
                            source: conn.source,
                            target: 'relay',
                            color: conn.color
                        })),
                        ...connections.map(conn => ({
                            source: 'relay',
                            target: conn.target,
                            color: conn.color
                        }))
                    ];

                    const linkGroup = svg.append('g').attr('stroke', '#999').attr('fill', 'none');
                    const linkPath = d3.linkHorizontal().x(d => d.x).y(d => d.y);

                    linkGroup.selectAll('path')
                        .data(links)
                        .enter()
                        .append('path')
                        .attr('d', d => linkPath({
                            source: nodesMap.get(d.source),
                            target: nodesMap.get(d.target)
                        }))
                        .attr('stroke-width', 2)
                        .attr('stroke', d => d.color || '#999')
                        .attr('marker-end', 'url(#arrow)');

                    svg.append('defs').append('marker')
                        .attr('id', 'arrow')
                        .attr('viewBox', '0 -5 10 10')
                        .attr('refX', 12)
                        .attr('refY', 0)
                        .attr('markerWidth', 6)
                        .attr('markerHeight', 6)
                        .attr('orient', 'auto')
                        .append('path')
                        .attr('d', 'M0,-5L10,0L0,5')
                        .attr('fill', '#999');

                    const nodeGroup = svg.append('g');
                    const node = nodeGroup.selectAll('g')
                        .data(nodes)
                        .enter()
                        .append('g')
                        .attr('transform', d => `translate(${d.x}, ${d.y})`)
                        .attr('cursor', 'pointer')
                        .on('click', (event, d) => {

                            svg.selectAll('circle').attr('stroke', '#fff').attr('stroke-width', 2);
                            d3.select(event.currentTarget).select('circle')
                                .attr('stroke', '#f59e0b')
                                .attr('stroke-width', 3);
                        });

                    node.append('circle')
                        .attr('r', 10)
                        .attr('fill', d => {
                            if (d.type === 'route') return '#3b82f6';
                            if (d.type === 'relay') return '#facc15';
                            if (d.type === 'ward') {
                                const count = wardConnectionCount[d.id]?.size ?? 0;
                                return count > 1 ? '#f43f5e' : '#10b981';
                            }
                            return '#ccc';
                        });

                    node.append('text')
                        .attr('dy', -15)
                        .attr('text-anchor', 'middle')
                        .text(d => d.name)
                        .attr('font-size', 10)
                        .attr('fill', '#374151');
                }
            }
        }
    </script>

    <style>
        .graph-container {
            overflow: hidden;
            overflow-y: scroll;
        }
    </style>
    @endpush
</x-filament-panels::page>