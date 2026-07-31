<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { store } from '@/actions/App/Http/Controllers/ProjectController';
import ProjectForm from '@/components/projects/ProjectForm.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/projects';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Projects',
                href: index(),
            },
            {
                title: 'Create project',
                href: create(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Create project" />

    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-4">
        <div class="item-start flex justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Create project
                </h1>
                <p class="my-1 text-sm text-muted-foreground">
                    Create a new project to start mapping dependencies.
                </p>
            </div>

            <Button variant="outline" as-child>
                <Link :href="index()">Back to Projects</Link>
            </Button>
        </div>

        <div class="rounded-xl border bg-card p-6 text-card-foreground">
            <ProjectForm :action="store.form()" submit-label="Create project" />
        </div>
    </div>
</template>
