<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { FolderKanban, Plus } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { create, index, show } from '@/routes/projects';
import type { Project } from '@/types';

defineProps<{
    projects: Project[];
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
    <Head title="Projects" />

    <div class="flex flex-col gap-6 p-4">
        <div class="flex items-start justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Projects</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create and manage your dependency maps.
                </p>
            </div>

            <Button as-child>
                <Link :href="create()">
                    <Plus class="size-4" />
                    New project
                </Link>
            </Button>
        </div>

        <div
            v-if="projects.length === 0"
            class="flex min-h-72 flex-col items-center justify-center gap-4 rounded-xl border border-dashed p-8 text-center"
        >
            <div
                class="flex size-12 items-center justify-center rounded-full bg-muted"
            >
                <FolderKanban class="size-6 text-muted-foreground" />
            </div>

            <div>
                <h2 class="font-medium">No projects yet</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Create your first project to start mapping dependencies.
                </p>
            </div>

            <Button as-child>
                <Link :href="create()">Create project</Link>
            </Button>
        </div>

        <div v-else class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="project in projects"
                :key="project.id"
                :href="show(project)"
                class="group rounded-xl border bg-card p-5 text-card-foreground transition-colors hover:bg-accent"
            >
                <div class="flex items-start justify-between gap-4">
                    <div
                        class="flex size-10 items-center justify-center rounded-lg bg-muted"
                    >
                        <FolderKanban class="size-5" />
                    </div>
                </div>

                <h2 class="mt-4 font-semibold group-hover:underline">
                    {{ project.name }}
                </h2>

                <p
                    v-if="project.description"
                    class="mt-2 line-clamp-2 text-sm text-muted-foreground"
                >
                    {{ project.description }}
                </p>

                <p v-else class="mt-2 text-sm text-muted-foreground">
                    No description
                </p>

                <div class="mt-5 flex gap-4 text-xs text-muted-foreground">
                    <span>{{ project.nodes_count ?? 0 }} nodes</span>
                    <span>
                        {{ project.dependencies_count ?? 0 }} dependencies
                    </span>
                </div>
            </Link>
        </div>
    </div>
</template>
