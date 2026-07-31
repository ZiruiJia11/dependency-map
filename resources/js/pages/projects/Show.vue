<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Network, Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import NodeForm from '@/components/nodes/NodeForm.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { destroy, edit, index } from '@/routes/projects';
import {
    destroy as destroyNode,
    store as storeNode,
    update as updateNode,
} from '@/routes/projects/nodes';
import type { Node, Project } from '@/types';

const props = defineProps<{
    project: Project;
}>();

const isCreateNodeOpen = ref(false);
const isEditNodeOpen = ref(false);
const editingNode = ref<Node | null>(null);

function openEditNode(node: Node): void {
    editingNode.value = node;
    isEditNodeOpen.value = true;
}

function finishEditNode(): void {
    isEditNodeOpen.value = false;
    editingNode.value = null;
}

function deleteNode(node: Node): void {
    const confirmed = window.confirm(
        `Are you sure you want to delete the node "${node.name}"? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    router.delete(
        destroyNode.url({
            project: props.project,
            node,
        }),
        {
            preserveScroll: true,
        },
    );
}

function deleteProject(): void {
    const confirmed = window.confirm(
        `Are you sure you want to delete the project "${props.project.name}"? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    router.delete(destroy.url(props.project));
}
defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: index(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="project.name" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex flex-col gap-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ project.name }}
                    </h1>

                    <p
                        v-if="project.description"
                        class="mt-2 max-w-2xl text-sm text-muted-foreground"
                    >
                        {{ project.description }}
                    </p>

                    <p v-else class="mt-2 text-sm text-muted-foreground">
                        No description provided
                    </p>
                </div>
            </div>

            <div class="flex gap-2">
                <Button variant="outline" as-child>
                    <Link :href="edit(project.id)">
                        <Pencil class="size-4" />
                        Edit
                    </Link>
                </Button>

                <Button variant="destructive" @click="deleteProject">
                    <Trash2 class="size-4" />
                    Delete
                </Button>
            </div>
        </div>

        <section
            class="flex min-h-72 flex-col items-center justify-center gap-3 rounded-xl border border-dashed bg-muted/20 p-8 text-center"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-muted"
            >
                <Network class="size-6 text-muted-foreground" />
            </div>

            <div>
                <h2 class="font-medium">Dependency Map</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Interactive dependency map for this project. Add nodes and
                    dependencies to visualize the relationships between them.
                </p>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-2">
            <section class="rounded-xl border bg-card text-card-foreground">
                <div
                    class="flex items-start justify-between gap-4 border-b p-5"
                >
                    <div>
                        <h2 class="font-semibold">Nodes</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Nodes are the individual components or entities
                            within your project.
                        </p>
                    </div>

                    <Dialog v-model:open="isCreateNodeOpen">
                        <DialogTrigger as-child>
                            <Button size="sm">
                                <Plus class="size-4" />
                                Add node
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Add node</DialogTitle>
                                <DialogDescription>
                                    Add a building block to this dependency map.
                                </DialogDescription>
                            </DialogHeader>

                            <NodeForm
                                :action="storeNode.form(project)"
                                submit-label="Create node"
                                @success="isCreateNodeOpen = false"
                            />
                        </DialogContent>
                    </Dialog>
                </div>

                <div
                    v-if="!project.nodes?.length"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    No nodes yet. Add a node to start mapping dependencies.
                </div>

                <div v-else class="divide-y">
                    <div
                        v-for="node in project.nodes"
                        :key="node.id"
                        class="flex items-start justify-between gap-4 p-5"
                    >
                        <div>
                            <h3 class="font-medium">{{ node.name }}</h3>
                            <p
                                v-if="node.description"
                                class="mt-1 line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ node.description }}
                            </p>
                        </div>

                        <div class="flex shrink-0 items-start gap-3">
                            <div class="flex flex-col items-end gap-2">
                                <Badge variant="outline">
                                    {{ node.type.replace('_', ' ') }}
                                </Badge>
                                <Badge variant="secondary">
                                    {{ node.status.replace('_', ' ') }}
                                </Badge>
                            </div>

                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Edit ${node.name}`"
                                @click="openEditNode(node)"
                            >
                                <Pencil class="size-4" />
                            </Button>

                            <Button
                                variant="ghost"
                                size="icon"
                                :aria-label="`Delete ${node.name}`"
                                class="text-destructive hover:text-destructive"
                                @click="deleteNode(node)"
                            >
                                <Trash2 class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>

                <Dialog v-model:open="isEditNodeOpen">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit node</DialogTitle>
                            <DialogDescription>
                                Update this node's details.
                            </DialogDescription>
                        </DialogHeader>

                        <NodeForm
                            v-if="editingNode"
                            :action="
                                updateNode.form({
                                    project,
                                    node: editingNode,
                                })
                            "
                            :node="editingNode"
                            submit-label="Save changes"
                            @success="finishEditNode"
                        />
                    </DialogContent>
                </Dialog>
            </section>

            <section class="rounded-xl border bg-card text-card-foreground">
                <div class="border-b p-5">
                    <h2 class="font-semibold">Dependencies</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Dependencies represent the relationships between nodes
                        in your project. They help you understand how different
                        components interact and rely on each other.
                    </p>
                </div>

                <div
                    v-if="!project.dependencies?.length"
                    class="p-8 text-center text-sm text-muted-foreground"
                >
                    No dependencies yet. Add a dependency to visualize the
                    relationships between nodes.
                </div>

                <div v-else class="divide-y">
                    <div
                        v-for="dependency in project.dependencies"
                        :key="dependency.id"
                        class="p-5"
                    >
                        <p class="text-sm font-medium">
                            {{ dependency.source_node.name }}
                            <span class="text-muted-foreground">→</span>
                            {{ dependency.target_node.name }}
                        </p>
                        <p
                            v-if="dependency.description"
                            class="mt-1 text-sm text-muted-foreground"
                        >
                            {{ dependency.description }}
                        </p>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
