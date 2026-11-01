<script setup lang="ts">
defineProps<{
    name: string
    model: string
    type: string
    status: string
}>();
</script>

<template>
    <div class="card card-row">
        <div class="thumb"></div>
        <div>
            <h3>{{ name }}</h3>
            <p class="meta">{{ model }}</p>
            <div class="tags">
                <span class="tag">{{ type }}</span>
                <span class="tag">{{ status }}</span>
            </div>
        </div>
        <span class="chevron">›</span>
    </div>
</template>
