<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Node } from '@/types';
import type { RouteFormDefinition } from '@/wayfinder';

defineProps<{
    action: RouteFormDefinition<'post'>;
    node?: Node;
    submitLabel: string;
}>();

const emit = defineEmits<{
    success: [];
}>();

const nodeTypes = [
    { value: 'task', label: 'Task' },
    { value: 'person', label: 'Person' },
    { value: 'service', label: 'Service' },
    { value: 'database', label: 'Database' },
    { value: 'supplier', label: 'Supplier' },
    { value: 'approval', label: 'Approval' },
    { value: 'other', label: 'Other' },
] as const;

const nodeStatuses = [
    { value: 'normal', label: 'Normal' },
    { value: 'at_risk', label: 'At risk' },
    { value: 'blocked', label: 'Blocked' },
    { value: 'complete', label: 'Complete' },
] as const;
</script>

<template>
    <Form
        v-bind="action"
        class="space-y-5"
        #default="{ errors, processing }"
        @success="emit('success')"
    >
        <div class="grid gap-2">
            <Label for="node-name">Name</Label>

            <Input
                id="node-name"
                name="name"
                :default-value="node?.name ?? ''"
                required
                maxlength="255"
                autocomplete="off"
                placeholder="Node name"
            />

            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="grid gap-2">
                <Label for="node-type">Type</Label>

                <select
                    id="node-type"
                    name="type"
                    :value="node?.type ?? 'task'"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option
                        v-for="type in nodeTypes"
                        :key="type.value"
                        :value="type.value"
                    >
                        {{ type.label }}
                    </option>
                </select>

                <InputError :message="errors.type" />
            </div>

            <div class="grid gap-2">
                <Label for="node-status">Status</Label>

                <select
                    id="node-status"
                    name="status"
                    :value="node?.status ?? 'normal'"
                    class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option
                        v-for="status in nodeStatuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>

                <InputError :message="errors.status" />
            </div>
        </div>

        <div class="grid gap-2">
            <Label for="node-description">Description</Label>

            <textarea
                id="node-description"
                name="description"
                :value="node?.description ?? ''"
                rows="4"
                maxlength="5000"
                placeholder="Describe this node"
                class="flex min-h-24 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />

            <InputError :message="errors.description" />
        </div>

        <Button type="submit" :disabled="processing">
            {{ processing ? 'Saving…' : submitLabel }}
        </Button>
    </Form>
</template>
