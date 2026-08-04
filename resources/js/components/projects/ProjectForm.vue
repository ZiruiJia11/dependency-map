<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import type { RouteFormDefinition } from '@/wayfinder';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Project } from '@/types';

defineProps<{
    // Wayfinder supplies the correct create or method-spoofed update action.
    action: RouteFormDefinition<'post'>;
    // When present, the same form operates in edit mode with existing values.
    project?: Project;
    submitLabel: string;
}>();
</script>

<template>
    <!-- Inertia handles submission state and maps Laravel validation errors. -->
    <Form v-bind="action" class="space-y-6" #default="{ errors, processing }">
        <div class="grid gap-2">
            <Label for="name">Name</Label>

            <Input
                id="name"
                name="name"
                :default-value="project?.name ?? ''"
                required
                maxlength="255"
                autocomplete="off"
                placeholder="Project name"
            />

            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-2">
            <Label for="description">Description</Label>

            <textarea
                id="description"
                name="description"
                :value="project?.description ?? ''"
                rows="6"
                maxlength="5000"
                placeholder="Describe the project"
                class="flex min-h-32 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
            />

            <InputError :message="errors.description" />
        </div>

        <Button type="submit" :disabled="processing">
            {{ processing ? 'Saving…' : submitLabel }}
        </Button>
    </Form>
</template>
