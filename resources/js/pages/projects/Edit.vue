<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { update } from '@/actions/App/Http/Controllers/ProjectController';
import ProjectForm from '@/components/projects/ProjectForm.vue';
import { Button } from '@/components/ui/button';
import { index, show } from '@/routes/projects';
import type { Project } from '@/types';

defineProps<{
    project: Project;
}>();

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
    <Head :title="'Edit ${project.name}'" />

    <div class="max-w-3x1 mx-auto flex w-full flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2x1 font-semibold tracking-tight">
                    Edit Project
                </h1>
                <p class="my-1 text-sm text-muted-foreground">
                    Update the project details.
                </p>
            </div>

            <Button variant="outline" as-child>
                <Link :href="show(project.id)">Back to Project</Link>
            </Button>
        </div>

        <div class="rounded-xl border bg-card p-6 text-card-foreground">
            <ProjectForm
                :action="update.form(project)"
                :project="project"
                submit-label="Update project"
            />
        </div>
    </div>
</template>
