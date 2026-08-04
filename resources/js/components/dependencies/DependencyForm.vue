<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import type { Dependency, Node } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

const props = defineProps<{
    // Wayfinder provides either the store action or a method-spoofed update action.
    action: RouteFormDefinition<'post'>;
    // Existing data is optional so this component supports create and edit modes.
    dependency?: Dependency;
    // Only nodes from the current project are offered as endpoints.
    nodes: Node[];
    submitLabel: string;
}>();

const emit = defineEmits<{
    success: [];
}>();

// New connections default to two different nodes; edits preserve existing endpoints.
const defaultSourceNodeId =
    props.dependency?.source_node_id ?? props.nodes[0]?.id;

const defaultTargetNodeId =
    props.dependency?.target_node_id ?? props.nodes[0]?.id;
</script>

<template>
    <!-- Inertia submits named fields and exposes Laravel validation errors here. -->
    <Form
        v-bind="action"
        class="space-y-5"
        #default="{ errors, processing }"
        @success="emit('success')"
    >
        <!-- A directed connection requires at least two available project nodes. -->
        <div
            v-if="nodes.length < 2"
            class="rounded-md border border-destructive/30 bg-destructive/10 p-3 text-sm text-destructive"
        >
            Add at least two nodes to create a dependency.
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="dependency-source-node">Source Node</Label>

                <select
                    id="dependency-source-node"
                    name="source_node_id"
                    :value="defaultSourceNodeId"
                    required
                    class="focus-cisible:border-ring h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option
                        v-for="node in nodes"
                        :key="node.id"
                        :value="node.id"
                    >
                        {{ node.name }}
                    </option>
                </select>

                <InputError :message="errors.source_node_id" />
            </div>

            <div class="grid gap-2">
                <Label for="dependency-target-node">Target Node</Label>

                <select
                    id="dependency-target-node"
                    name="target_node_id"
                    :value="defaultTargetNodeId"
                    required
                    class="focus-cisible:border-ring h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option
                        v-for="node in nodes"
                        :key="node.id"
                        :value="node.id"
                    >
                        {{ node.name }}
                    </option>
                </select>

                <InputError :message="errors.target_node_id" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="dependency-description">Description</Label>

            <textarea
                id="dependency-description"
                name="description"
                :value="dependency?.description ?? ''"
                rows="4"
                maxlength="5000"
                placeholder="Describe this dependency"
                class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />

            <InputError :message="errors.description" />
        </div>

        <Button
            type="submit"
            :disabled="processing || nodes.length < 2"
            class="w-full sm:w-auto"
        >
            {{ processing ? 'Saving...' : submitLabel }}
        </Button>
    </Form>
</template>
