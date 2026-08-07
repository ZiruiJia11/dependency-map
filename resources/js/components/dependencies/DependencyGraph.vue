<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, ref, useTemplateRef } from 'vue';

import { position as updateNodePosition } from '@/routes/projects/nodes';
import { destroy as resetNodePositions } from '@/routes/projects/nodes/positions';
import type { Dependency, Node } from '@/types';

const props = defineProps<{
    nodes: Node[];
    dependencies: Dependency[];
}>();

// Controls the overall size of the graph and its circular layout.
// Uses a wide and compact canvas for the dependency graph.
const canvas = {
    width: 1200,
    height: 260,
    centerX: 600,
    centerY: 130,
    radiusX: 400,
    radiusY: 85,
};

// Controls the size of each node card.
const nodeSize = {
    width: 130,
    height: 52,
};

interface PositionedNode extends Node {
    x: number;
    y: number;
}

interface PositionedDependency {
    id: number;
    sourceX: number;
    sourceY: number;
    targetX: number;
    targetY: number;
}

interface CanvasPoint {
    x: number;
    y: number;
}

const draggedPosition = ref<Record<number, CanvasPoint>>({});

const hasCustomPositions = computed(() => {
    const hasLocalPositions = Object.keys(draggedPosition.value).length > 0;

    const hasSavedPositions = props.nodes.some(
        (node) => node.position_x !== null && node.position_y !== null,
    );

    return hasLocalPositions || hasSavedPositions;
});

const graphRef = useTemplateRef<SVGSVGElement>('graphSvg');

const activeNodeId = ref<number | null>(null);

const dragOffset = ref<CanvasPoint>({
    x: 0,
    y: 0,
});

// Distributes all nodes evenly around an ellipse.
const positionedNodes = computed<PositionedNode[]>(() => {
    return props.nodes.map((node, index) => {
        const nodeDraggedPosition = draggedPosition.value[node.id];

        if (nodeDraggedPosition) {
            return {
                ...node,
                ...nodeDraggedPosition,
            };
        }

        if (node.position_x !== null && node.position_y !== null) {
            const savedX = Number(node.position_x);
            const savedY = Number(node.position_y);

            if (Number.isFinite(savedX) && Number.isFinite(savedY)) {
                return {
                    ...node,
                    x: savedX,
                    y: savedY,
                };
            }
        }

        // Keep a single node in the centre of the graph.
        if (props.nodes.length === 1) {
            return {
                ...node,
                x: canvas.centerX,
                y: canvas.centerY,
            };
        }

        // Convert the node index into an angle around a full circle.
        // Subtracting PI / 2 makes the first node start at the top.
        const angle = (index / props.nodes.length) * Math.PI * 2 - Math.PI / 2;

        return {
            ...node,
            x: canvas.centerX + Math.cos(angle) * canvas.radiusX,
            y: canvas.centerY + Math.sin(angle) * canvas.radiusY,
        };
    });
});

function getCanvasPoint(event: PointerEvent): CanvasPoint | null {
    const svg = graphRef.value;
    const screenMatrix = svg?.getScreenCTM();

    if (!svg || !screenMatrix) {
        return null;
    }

    const point = svg.createSVGPoint();

    point.x = event.clientX;
    point.y = event.clientY;

    const transformedPoint = point.matrixTransform(screenMatrix.inverse());

    return {
        x: transformedPoint.x,
        y: transformedPoint.y,
    };
}

function startNodeDrag(event: PointerEvent, node: PositionedNode): void {
    const pointer = getCanvasPoint(event);

    if (!pointer) {
        return;
    }

    event.preventDefault();

    const target = event.currentTarget as SVGElement;

    target.setPointerCapture(event.pointerId);

    activeNodeId.value = node.id;

    dragOffset.value = {
        x: pointer.x - node.x,
        y: pointer.y - node.y,
    };
}

function moveNode(event: PointerEvent): void {
    if (activeNodeId.value === null) {
        return;
    }

    const pointer = getCanvasPoint(event);

    if (!pointer) {
        return;
    }

    event.preventDefault();

    const halfWidth = nodeSize.width / 2;
    const halfHeight = nodeSize.height / 2;

    const position: CanvasPoint = {
        x: Math.min(
            canvas.width - halfWidth,
            Math.max(pointer.x - dragOffset.value.x, halfWidth),
        ),
        y: Math.min(
            canvas.height - halfHeight,
            Math.max(pointer.y - dragOffset.value.y, halfHeight),
        ),
    };

    draggedPosition.value = {
        ...draggedPosition.value,
        [activeNodeId.value]: position,
    };
}

function stopNodeDrag(event: PointerEvent): void {
    const target = event.currentTarget as SVGElement;
    const nodeId = activeNodeId.value;

    if (target.hasPointerCapture(event.pointerId)) {
        target.releasePointerCapture(event.pointerId);
    }

    activeNodeId.value = null;

    if (nodeId === null) {
        return;
    }

    const node = props.nodes.find((item) => item.id === nodeId);
    const position = draggedPosition.value[nodeId];

    if (!node || !position) {
        return;
    }

    router.patch(
        updateNodePosition.url({
            project: node.project_id,
            node: node.id,
        }),
        {
            position_x: position.x,
            position_y: position.y,
        },
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
}

function resetLayout(): void {
    const projectId = props.nodes[0]?.project_id;

    if (projectId === undefined) {
        draggedPosition.value = {};
        activeNodeId.value = null;

        return;
    }

    router.delete(resetNodePositions.url(projectId), {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => {
            draggedPosition.value = {};
            activeNodeId.value = null;
        },
    });
}

// Converts dependencies into SVG line coordinates.
const positionedDependencies = computed<PositionedDependency[]>(() => {
    // Provides fast lookup of positioned nodes by their database ID.
    const nodesById = new Map(
        positionedNodes.value.map((node) => [node.id, node]),
    );

    return props.dependencies.flatMap((dependency) => {
        const source = nodesById.get(dependency.source_node_id);
        const target = nodesById.get(dependency.target_node_id);

        // Ignore invalid dependencies if one of their nodes is unavailable.
        if (!source || !target) {
            return [];
        }

        const differenceX = target.x - source.x;
        const differenceY = target.y - source.y;
        const distance = Math.hypot(differenceX, differenceY);

        // A zero-distance line cannot have a direction.
        if (distance === 0) {
            return [];
        }

        // A unit vector describes the direction from source to target.
        const directionX = differenceX / distance;
        const directionY = differenceY / distance;

        // Move both ends away from the node centres.
        // This keeps the line outside the cards and makes the arrow visible.
        const horizontalOffset = nodeSize.width / 2 + 8;
        const verticalOffset = nodeSize.height / 2 + 8;

        const offset = Math.min(
            horizontalOffset / Math.max(Math.abs(directionX), Number.EPSILON),
            verticalOffset / Math.max(Math.abs(directionY), Number.EPSILON),
        );

        return [
            {
                id: dependency.id,
                sourceX: source.x + directionX * offset,
                sourceY: source.y + directionY * offset,
                targetX: target.x - directionX * offset,
                targetY: target.y - directionY * offset,
            },
        ];
    });
});

// Maps each node status to a visual style.
function getNodeClasses(status: string): string {
    const statusClasses: Record<string, string> = {
        normal: 'fill-card stroke-border',
        at_risk:
            'fill-amber-50 stroke-amber-500 dark:fill-amber-950/40 dark:stroke-amber-400',
        completed:
            'fill-green-50 stroke-green-500 dark:fill-green-950/40 dark:stroke-green-400',
        blocked:
            'fill-red-50 stroke-red-500 dark:fill-red-950/40 dark:stroke-red-400',
    };

    return statusClasses[status] ?? statusClasses.normal;
}
</script>

<template>
    <section class="rounded-xl border bg-card text-card-foreground">
        <div class="flex items-start justify-between gap-4 border-b px-5 py-4">
            <div>
                <h2 class="font-semibold">Dependency Map</h2>

                <p class="mt-1 text-sm text-muted-foreground">
                    Drag nodes to rearrange the project map.
                </p>
            </div>

            <button
                type="button"
                :disabled="!hasCustomPositions"
                class="rounded-md border bg-background px-3 py-1.5 text-xs font-medium shadow-sm transition-colors hover:bg-muted disabled:cursor-not-allowed disabled:opacity-50 disabled:hover:bg-background"
                @click="resetLayout"
            >
                Reset layout
            </button>
        </div>

        <div
            class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 text-sm text-muted-foreground"
        >
            <div class="flex gap-4">
                <span>{{ nodes.length }} nodes</span>
                <span>{{ dependencies.length }} dependencies</span>
            </div>

            <div class="flex flex-wrap items-center gap-3 text-xs">
                <span class="flex items-center gap-1.5">
                    <span class="size-2.5 rounded-full border bg-card"></span>
                    Normal
                </span>

                <span class="flex items-center gap-1.5">
                    <span
                        class="size-2.5 rounded-full border border-amber-500 bg-amber-100"
                    ></span>
                    At risk
                </span>

                <span class="flex items-center gap-1.5">
                    <span
                        class="size-2.5 rounded-full border border-red-500 bg-red-100"
                    ></span>
                    Blocked
                </span>

                <span class="flex items-center gap-1.5">
                    <span
                        class="size-2.5 rounded-full border border-green-500 bg-green-100"
                    ></span>
                    Complete
                </span>
            </div>
        </div>

        <div
            v-if="nodes.length > 0"
            class="overflow-x-auto border-t bg-muted/10 p-3"
        >
            <svg
                ref="graphSvg"
                :viewBox="`0 0 ${canvas.width} ${canvas.height}`"
                class="h-60 w-full min-w-xl"
                role="img"
                aria-label="Project dependency graph"
            >
                <defs>
                    <!-- Defines the arrow displayed at the target end. -->
                    <marker
                        id="dependency-arrow"
                        markerWidth="8"
                        markerHeight="8"
                        refX="7"
                        refY="4"
                        orient="auto"
                        markerUnits="strokeWidth"
                    >
                        <path
                            d="M 0 0 L 8 4 L 0 8 Z"
                            class="fill-muted-foreground"
                        />
                    </marker>
                </defs>

                <!-- Render lines first so they appear behind the nodes. -->
                <line
                    v-for="dependency in positionedDependencies"
                    :key="dependency.id"
                    :x1="dependency.sourceX"
                    :y1="dependency.sourceY"
                    :x2="dependency.targetX"
                    :y2="dependency.targetY"
                    class="stroke-muted-foreground"
                    stroke-width="2"
                    marker-end="url(#dependency-arrow)"
                />

                <!-- Render one SVG group for every node. -->
                <g
                    v-for="node in positionedNodes"
                    :key="node.id"
                    :transform="`
                        translate(
                            ${node.x - nodeSize.width / 2}
                            ${node.y - nodeSize.height / 2}
                        )
                    `"
                    :class="
                        activeNodeId === node.id
                            ? 'cursor-grabbing'
                            : 'cursor-grab'
                    "
                    style="touch-action: none"
                    @pointerdown="startNodeDrag($event, node)"
                    @pointermove="moveNode"
                    @pointerup="stopNodeDrag"
                    @pointercancel="stopNodeDrag"
                >
                    <rect
                        :width="nodeSize.width"
                        :height="nodeSize.height"
                        rx="10"
                        :class="getNodeClasses(node.status)"
                        stroke-width="2"
                    />

                    <text
                        :x="nodeSize.width / 2"
                        y="23"
                        text-anchor="middle"
                        class="fill-foreground text-xs font-semibold"
                    >
                        {{
                            node.name.length > 16
                                ? `${node.name.slice(0, 16)}…`
                                : node.name
                        }}
                    </text>

                    <text
                        :x="nodeSize.width / 2"
                        y="40"
                        text-anchor="middle"
                        class="fill-muted-foreground text-[10px]"
                    >
                        {{ node.type.replaceAll('_', ' ') }}
                        ·
                        {{ node.status.replaceAll('_', ' ') }}
                    </text>
                </g>
            </svg>
        </div>

        <div
            v-else
            class="border-t p-8 text-center text-sm text-muted-foreground"
        >
            Add a node to start building the dependency map.
        </div>
    </section>
</template>
