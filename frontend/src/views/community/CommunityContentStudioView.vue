<template>
<section class="pv-page pv-content-studio-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>{{ contentStudioTitle }}</h1><p>{{ contentStudioSubtitle }}</p></div>
          <router-link :to="contentStudioBackHref" class="pv-small-button"><PvIcon name="arrow-left" /> {{ contentStudioBackLabel }}</router-link>
        </header>

        <article v-if="!authStore.isAuthenticated" class="pv-panel pv-empty-inline">
          <PvIcon name="lock" />
          <strong>Sign in required</strong>
          <p>Staff and content editors can submit content after signing in.</p>
          <router-link to="/login" class="pv-primary-button">Sign in</router-link>
        </article>

        <article v-else-if="!contentStudioLoaded" class="pv-panel pv-empty-inline">
          <PvIcon name="clock" />
          <strong>Loading content access</strong>
          <p>Checking your frontend publishing permissions.</p>
        </article>

        <article v-else-if="contentStudioLoaded && !canUseContentStudio" class="pv-panel pv-empty-inline">
          <PvIcon name="shield" />
          <strong>No content permissions</strong>
          <p>Your account needs the staff or content-editor role, or the community content permissions, before you can submit content.</p>
        </article>

        <form v-else class="pv-form-card pv-content-studio-form" @submit.prevent="saveContentStudioItem('draft')">
          <p v-if="contentStudioStatusMessage" class="pv-alert pv-alert--compact">{{ contentStudioStatusMessage }}</p>
          <div class="pv-content-studio-context">
            <span class="pv-icon-tile"><PvIcon :name="contentStudioIcon" /></span>
            <span><strong>{{ contentStudioDestinationLabel }}</strong><small>{{ contentStudioContextLabel }}</small></span>
            <em>{{ contentStudioModeLabel }}</em>
          </div>
          <div v-if="contentStudioIsGeneric" class="pv-content-type-picker" role="group" aria-label="Content type">
            <button type="button" :class="{ active: contentStudioForm.type === 'research' }" @click="setContentStudioType('research')"><PvIcon name="library" /><span><strong>Research</strong><small>Library article</small></span></button>
            <button type="button" :class="{ active: contentStudioForm.type === 'guide' }" @click="setContentStudioType('guide')"><PvIcon name="document" /><span><strong>Guide</strong><small>Step-by-step</small></span></button>
            <button type="button" :class="{ active: contentStudioForm.type === 'faq' }" @click="setContentStudioType('faq')"><PvIcon name="question" /><span><strong>FAQ</strong><small>Quick answer</small></span></button>
          </div>

          <div class="pv-form-row">
            <label>{{ contentStudioTitleLabel }}<input v-model="contentStudioForm.title" required maxlength="220" :placeholder="contentStudioTitlePlaceholder"></label>
            <label>{{ contentStudioCategoryLabel }}<input v-model="contentStudioForm.category" maxlength="100" :placeholder="contentStudioCategoryPlaceholder"></label>
          </div>
          <div class="pv-form-row">
            <label>{{ contentStudioTagLabel }}<input v-model="contentStudioForm.tag" maxlength="80" :placeholder="contentStudioTagPlaceholder"></label>
            <label v-if="contentStudioForm.type !== 'faq'">Read Time<input v-model.number="contentStudioForm.read_minutes" min="1" max="240" type="number"></label>
          </div>
          <label>{{ contentStudioExcerptLabel }}<textarea v-model="contentStudioForm.excerpt" maxlength="500" rows="3" :placeholder="contentStudioExcerptPlaceholder"></textarea><small>{{ contentStudioForm.excerpt.length }}/500</small></label>
          <div v-if="contentStudioForm.type === 'research'" class="pv-form-row">
            <label>Compound<input v-model="contentStudioForm.metadata_compound" maxlength="160" placeholder="Retatrutide, BPC-157..."></label>
            <label>Research Focus<input v-model="contentStudioForm.metadata_research_focus" maxlength="160" placeholder="Safety profile, mechanism, outcomes..."></label>
          </div>
          <label v-if="contentStudioForm.type === 'research'">Figures &amp; Data<textarea v-model="contentStudioForm.metadata_figures_data" maxlength="4000" rows="4" placeholder="Summarise tables, figures, measurements, or data notes shown on the article data tab."></textarea></label>
          <label v-if="contentStudioForm.type === 'research'">References<textarea v-model="contentStudioForm.metadata_references" maxlength="4000" rows="4" placeholder="Add citations, source links, study notes, or reference text for the references tab."></textarea></label>
          <div v-if="contentStudioForm.type === 'research'" class="pv-content-studio-note"><PvIcon name="message" /><span><strong>Comments</strong><small>Published articles show comments on the article page; staff do not edit reader comments here.</small></span></div>
          <div v-else-if="contentStudioForm.type === 'guide'" class="pv-form-row">
            <label>Difficulty<select v-model="contentStudioForm.metadata_difficulty"><option>Beginner</option><option>Intermediate</option><option>Advanced</option></select></label>
            <label>Guide Type<input v-model="contentStudioForm.metadata_guide_type" maxlength="80" placeholder="Tutorial, Checklist, Reference..."></label>
          </div>
          <label>{{ contentStudioBodyLabel }}<TipTapComposer :key="'content-studio-' + contentStudioEditorKey" v-model="contentStudioForm.body" :placeholder="contentStudioBodyPlaceholder" :max-length="50000" /></label>
          <div v-if="contentStudioForm.type !== 'faq'" class="pv-form-row pv-form-row--single">
            <label>Image URL<input v-model="contentStudioForm.image_url" type="url" placeholder="https://example.com/image.jpg"></label>
          </div>
          <div class="pv-content-studio-note">
            <PvIcon name="shield" />
            <span><strong>{{ contentStudioStatusTitle }}</strong><small>{{ contentStudioStatusLabel }}</small></span>
          </div>
          <footer class="pv-content-studio-actions">
            <button type="button" class="pv-small-button" @click="resetContentStudioForm">Clear</button>
            <button type="button" class="pv-small-button" :disabled="contentStudioSaving" @click="saveContentStudioItem('draft')"><PvIcon name="document" /> {{ contentStudioSaving ? 'Saving...' : contentStudioDraftButtonLabel }}</button>
            <button v-if="canPublishContent" type="button" class="pv-primary-button" :disabled="contentStudioSaving" @click="saveContentStudioItem('published')"><PvIcon name="send" /> {{ contentStudioSaving ? 'Publishing...' : contentStudioPublishButtonLabel }}</button>
          </footer>
        </form>
      </main>

      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Frontend Access</h2>
          <dl class="pv-data-list">
            <div><dt>Create</dt><dd>{{ contentStudioPermissions.can_create ? 'Allowed' : 'No' }}</dd></div>
            <div><dt>Publish</dt><dd>{{ contentStudioPermissions.can_publish ? 'Allowed' : 'Draft only' }}</dd></div>
            <div><dt>Scope</dt><dd>{{ contentStudioPermissions.can_manage ? 'All content' : 'Own submissions' }}</dd></div>
          </dl>
        </article>
        <article v-if="canUseContentStudio" class="pv-panel">
          <header class="pv-panel-header">
            <h2>{{ contentStudioQueueTitle }}</h2>
            <button class="pv-small-button" type="button" @click="loadContentStudioItems">Refresh</button>
          </header>
          <div class="pv-content-queue-filter" role="group" aria-label="Queue status">
            <button type="button" :class="{ active: contentStudioQueueFilter === 'all' }" @click="contentStudioQueueFilter = 'all'">All</button>
            <button type="button" :class="{ active: contentStudioQueueFilter === 'draft' }" @click="contentStudioQueueFilter = 'draft'">Drafts</button>
            <button v-if="canPublishContent" type="button" :class="{ active: contentStudioQueueFilter === 'published' }" @click="contentStudioQueueFilter = 'published'">Published</button>
          </div>
          <div class="pv-content-studio-list">
            <button v-for="item in filteredContentStudioItems" :key="item.slug" type="button" @click="editContentStudioItem(item)">
              <span><strong>{{ item.title }}</strong><small>{{ contentStudioItemTypeLabel(item.type) }} · {{ item.category || 'General' }}</small></span>
              <em :class="`status-${item.status}`">{{ item.status }}</em>
            </button>
            <p v-if="contentStudioLoaded && filteredContentStudioItems.length === 0" class="pv-muted">{{ contentStudioEmptyQueueText }}</p>
          </div>
        </article>
        <article class="pv-panel">
          <h2>{{ contentStudioWorkflowTitle }}</h2>
          <p class="pv-muted">{{ contentStudioWorkflowCopy }}</p>
        </article>
      </aside>
    </div>
  </section>
</template>

<script lang="ts">
/* eslint-disable @typescript-eslint/no-explicit-any */
import { inject } from 'vue';
export default {
  setup() {
    const state = inject('communityState');
    if (!state) throw new Error('Missing community state');
    return state as any;
  }
}
</script>
