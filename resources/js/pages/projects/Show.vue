<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Plus, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import DependencyForm from '@/components/dependencies/DependencyForm.vue';
import DependencyGraph from '@/components/dependencies/DependencyGraph.vue';
import NodeForm from '@/components/nodes/NodeForm.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
    store as storeDependency,
    update as updateDependency,
    destroy as destroyDependency,
} from '@/routes/projects/dependencies';
import {
    destroy as destroyNode,
    store as storeNode,
    update as updateNode,
} from '@/routes/projects/nodes';
import type { Dependency, Node, Project } from '@/types';

const props = defineProps<{
    project: Project;
}>();

// Each dialog owns independent state so node and dependency workflows do not conflict.
const isCreateNodeOpen = ref(false);
const isCreateDependencyOpen = ref(false);
const isEditDependencyOpen = ref(false);
const editingDependency = ref<Dependency | null>(null);
const isEditNodeOpen = ref(false);
const editingNode = ref<Node | null>(null);

function openEditNode(node: Node): void {
    // Store the selected row so the shared form can prefill its current values.
    editingNode.value = node;
    isEditNodeOpen.value = true;
}

function finishEditNode(): void {
    isEditNodeOpen.value = false;
    editingNode.value = null;
}

function deleteNode(node: Node): void {
    // Destructive actions require explicit confirmation before the Inertia request.
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

function openEditDependency(dependency: Dependency): void {
    // The edit dialog reuses DependencyForm with this selected connection.
    editingDependency.value = dependency;
    isEditDependencyOpen.value = true;
}

function finishEditDependency(): void {
    isEditDependencyOpen.value = false;
    editingDependency.value = null;
}

function deleteDependency(dependency: Dependency): void {
    // Wayfinder builds the nested project/dependency URL without hardcoding IDs.
    const confirmed = window.confirm(
        `Are you sure you want to delete the dependency from "${dependency.source_node.name}" to "${dependency.target_node.name}"? This action cannot be undone.`,
    );

    if (!confirmed) {
        return;
    }

    router.delete(
        destroyDependency.url({
            project: props.project,
            dependency,
        }),
        {
            preserveScroll: true,
        },
    );
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
        <!-- Project identity and top-level actions. -->
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

        <DependencyGraph
            :nodes="project.nodes ?? []"
            :dependencies="project.dependencies ?? []"
        />

        <div class="grid gap-6 lg:grid-cols-2">
            <!-- Node CRUD is managed inline so users stay on the project page. -->
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

                <Dialog v-model:open="isEditDependencyOpen">
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Edit dependency</DialogTitle>
                            <DialogDescription>
                                Update this dependency's details.
                            </DialogDescription>
                        </DialogHeader>

                        <DependencyForm
                            v-if="editingDependency"
                            :action="
                                updateDependency.form({
                                    project,
                                    dependency: editingDependency,
                                })
                            "
                            :dependency="editingDependency"
                            :nodes="project.nodes ?? []"
                            submit-label="Save changes"
                            @success="finishEditDependency"
                        />
                    </DialogContent>
                </Dialog>

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

            <!-- Directed dependencies connect two nodes from this project. -->
            <section class="rounded-xl border bg-card text-card-foreground">
                <div
                    class="flex items-start justify-between gap-4 border-b p-5"
                >
                    <div>
                        <h2 class="font-semibold">Dependencies</h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Dependencies represent the relationships between
                            nodes in your project. They help you understand how
                            different components interact and rely on each
                            other.
                        </p>
                    </div>

                    <Dialog v-model:open="isCreateDependencyOpen">
                        <DialogTrigger as-child>
                            <Button
                                size="sm"
                                :disabled="(project.nodes?.length ?? 0) < 2"
                            >
                                <Plus class="size-4" />
                                Add dependency
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Add dependency</DialogTitle>
                                <DialogDescription>
                                    Connect two nodes to represent a
                                    relationship between them.
                                </DialogDescription>
                            </DialogHeader>

                            <DependencyForm
                                :action="storeDependency.form(project)"
                                :nodes="project.nodes ?? []"
                                submit-label="Create dependency"
                                @success="isCreateDependencyOpen = false"
                            />
                        </DialogContent>
                    </Dialog>
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
                        class="flex items-start justify-between gap-4 p-5"
                    >
                        <div>
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

                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="`Edit dependency from ${dependency.source_node.name} to ${dependency.target_node.name}`"
                            @click="openEditDependency(dependency)"
                        >
                            <Pencil class="size-4" />
                        </Button>

                        <BUtton
                            variant="ghost"
                            size="icon"
                            :aria-label="`Delete dependency from ${dependency.source_node.name} to ${dependency.target_node.name}`"
                            class="text-destructive hover:text-destructive"
                            @click="deleteDependency(dependency)"
                        >
                            <Trash2 class="size-4" />
                        </BUtton>
                    </div>
                </div>
            </section>
        </div>
    </div>
</template>
