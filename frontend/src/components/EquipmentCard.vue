<script setup lang="ts">
defineProps<{
    name: string
    model: string
    type: string
    status: string
}>();
</script>

<template>
    <div class="card">
        <div class="thumb"></div>

        <div class="info">
            <h3 class="title">{{ name }}</h3>
            <p class="subtitle">{{ model }}</p>
            <div class="tags">
                <span class="tag">{{ type }}</span>
                <span class="tag">{{ status }}</span>
            </div>
        </div>

        <span class="chevron">›</span>
    </div>
</template>

<style scoped>
.card {
  display: flex;
  align-items: center;
  gap: 18px;
  padding: 14px 18px 14px 14px;
  background: #ffffff;
  border-radius: 18px;
  box-shadow: 0 4px 18px rgba(15, 27, 45, 0.06);
}

.thumb {
  flex-shrink: 0;
  width: 104px;
  height: 104px;
  border-radius: 12px;
  background: linear-gradient(145deg, #f3f4f6, #e7e9ed);
}

.info {
  flex: 1;
  min-width: 0;
}

.title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  line-height: 1.3;
  color: #0f1b2d;
}

.subtitle {
  margin: 4px 0 12px;
  font-size: 14px;
  color: #6b7280;
}

.tags {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.tag {
  padding: 4px 11px;
  font-size: 13px;
  color: #4b5563;
  background: #f1f2f4;
  border-radius: 999px;
}

.chevron {
  flex-shrink: 0;
  font-size: 28px;
  color: #1f2937;
}
</style>
