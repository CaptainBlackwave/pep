<template>
  <div class="emoji-picker-wrapper">
    <button
      type="button"
      class="pv-icon-button pv-icon-button--static"
      aria-label="Insert emoji"
      @click="toggle"
    >
      <PvIcon name="emoji" />
    </button>
    <div v-if="isOpen" class="emoji-picker-dropdown" ref="pickerContainer"></div>
  </div>
</template>

<script setup lang="ts">
import { ref, nextTick, onBeforeUnmount } from 'vue'
import { Picker } from 'emoji-mart'
import PvIcon from '@/components/peptide/PvIcon.vue'

type EmojiMartData = Record<string, unknown>
type EmojiSelection = {
  native?: string
}

let dataCache: EmojiMartData | null = null

async function loadData(): Promise<EmojiMartData> {
  if (!dataCache) {
    const response = await fetch(
      'https://cdn.jsdelivr.net/npm/@emoji-mart/data'
    )
    dataCache = await response.json()
  }

  if (!dataCache) {
    throw new Error('Emoji data failed to load')
  }

  return dataCache
}

const props = defineProps<{
  modelValue: string
}>()

const emit = defineEmits<{
  'update:modelValue': [value: string]
}>()

const isOpen = ref(false)
const pickerContainer = ref<HTMLDivElement>()
let picker: Picker | null = null

async function open() {
  if (picker) return
  isOpen.value = true
  await nextTick()
  if (!pickerContainer.value) return
  picker = new Picker({
    data: loadData,
    onEmojiSelect: (emoji: EmojiSelection) => {
      emit('update:modelValue', props.modelValue + (emoji.native ?? ''))
      isOpen.value = false
      picker = null
    },
    onClickOutside: () => {
      isOpen.value = false
      picker = null
    },
    theme: 'auto',
    previewPosition: 'none',
    skinTonePosition: 'search',
  })
  pickerContainer.value.appendChild(picker as unknown as Node)
}

function toggle() {
  if (isOpen.value) {
    isOpen.value = false
    picker = null
  } else {
    open()
  }
}

onBeforeUnmount(() => {
  picker = null
})
</script>

<style scoped>
.emoji-picker-wrapper {
  position: relative;
  display: inline-flex;
}

.emoji-picker-dropdown {
  position: absolute;
  bottom: 100%;
  right: 0;
  margin-bottom: 4px;
  z-index: 1000;
}

.emoji-picker-dropdown :deep(emoji-mart) {
  --emoji-mart-border-radius: 8px;
  --emoji-mart-box-shadow: 0 4px 24px rgba(0, 0, 0, 0.4);
}
</style>
