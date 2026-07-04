<template>
  <div v-if="showNewDiscussion" class="pv-modal-backdrop" @click.self="closeNewDiscussion">
    <form class="pv-modal" @submit.prevent="submitNewDiscussion">
      <header class="pv-panel-header">
        <div>
          <h2>New Discussion</h2>
          <p class="pv-muted">Start a community topic.</p>
        </div>
        <button type="button" class="pv-icon-button" aria-label="Close" @click="closeNewDiscussion">
          <PvIcon name="close" />
        </button>
      </header>
      <p v-if="discussionFormError" class="pv-alert pv-alert--compact">{{ discussionFormError }}</p>
      <label>
        Title
        <input v-model="newDiscussion.title" required maxlength="160" placeholder="What would you like to discuss?">
      </label>
      <label>
        Category
        <select v-model="newDiscussion.category_slug">
          <option v-for="category in discussionCategories" :key="category.slug" :value="category.slug">
            {{ category.name }}
          </option>
        </select>
      </label>
      <label>
        Message
        <div class="pv-textarea-with-emoji">
          <textarea v-model="newDiscussion.body" required rows="7" maxlength="10000" placeholder="Share the details, context, and what kind of help or feedback you want."></textarea>
          <EmojiPicker v-model="newDiscussion.body" />
        </div>
        <small>{{ newDiscussion.body.length }}/10000</small>
      </label>
      <footer>
        <button type="button" class="pv-small-button" @click="closeNewDiscussion">Cancel</button>
        <button type="submit" class="pv-primary-button" :disabled="creatingDiscussion">
          <PvIcon name="send" />
          {{ creatingDiscussion ? 'Posting...' : 'Post Discussion' }}
        </button>
      </footer>
    </form>
  </div>

  <div v-if="showSubmitLabResult" class="pv-modal-backdrop" @click.self="closeSubmitLabResult">
    <form class="pv-modal" @submit.prevent="submitLabResult">
      <header class="pv-panel-header">
        <div>
          <h2>Submit Lab Result</h2>
          <p class="pv-muted">Share a batch-specific report for admin review.</p>
        </div>
        <button type="button" class="pv-icon-button" aria-label="Close" @click="closeSubmitLabResult">
          <PvIcon name="close" />
        </button>
      </header>
      <p v-if="labFormError" class="pv-alert pv-alert--compact">{{ labFormError }}</p>
      <label>
        Compound
        <input v-model="newLabResult.compound_name" required maxlength="160" placeholder="Compound name">
      </label>
      <div class="pv-two-col">
        <label>
          Compound Type
          <input v-model="newLabResult.compound_type" maxlength="80" placeholder="Compound type">
        </label>
        <label>
          Use Case
          <input v-model="newLabResult.use_case" maxlength="120" placeholder="Research category">
        </label>
      </div>
      <div class="pv-two-col">
        <label>
          Vendor
          <input v-model="newLabResult.vendor_name" required maxlength="160" placeholder="Vendor name">
        </label>
        <label>
          Batch
          <input v-model="newLabResult.batch_code" required maxlength="120" placeholder="Batch code">
        </label>
      </div>
      <div class="pv-two-col">
        <label>
          Lab
          <input v-model="newLabResult.lab_name" required maxlength="160" placeholder="Testing lab">
        </label>
        <label>
          Tested Date
          <input v-model="newLabResult.tested_at" type="date">
        </label>
      </div>
      <div class="pv-two-col">
        <label>
          Purity %
          <input v-model.number="newLabResult.purity_percent" type="number" min="0" max="100" step="0.01" placeholder="Purity percentage">
        </label>
        <label>
          COA filename
          <input v-model="newLabResult.coa_filename" maxlength="180" placeholder="report.pdf">
        </label>
      </div>
      <label>
        Notes
        <div class="pv-textarea-with-emoji">
          <textarea v-model="newLabResult.notes" rows="4" maxlength="4000" placeholder="Add relevant report notes or context for moderators."></textarea>
          <EmojiPicker v-model="newLabResult.notes" />
        </div>
      </label>
      <footer>
        <button type="button" class="pv-small-button" @click="closeSubmitLabResult">Cancel</button>
        <button type="submit" class="pv-primary-button" :disabled="submittingLabResult">
          <PvIcon name="send" />
          {{ submittingLabResult ? 'Submitting...' : 'Submit for Review' }}
        </button>
      </footer>
    </form>
  </div>

  <section v-if="page === 'home'" class="pv-page pv-dashboard">
    <div class="pv-dashboard-grid">
      <div class="pv-stack">
        <article class="pv-hero" :style="{ backgroundImage: `linear-gradient(90deg, rgba(13,14,22,.96), rgba(13,14,22,.72) 43%, rgba(13,14,22,.22)), url(${heroImage})` }">
          <div>
            <h1>A trusted community for peptide information.</h1>
            <p>Real reviews. Real lab results. Real people.</p>
          </div>
          <div class="pv-hero-points">
            <span><PvIcon name="shield" /> Independent Reviews</span>
            <span><PvIcon name="flask" /> Lab Verified</span>
            <span><PvIcon name="users" /> Community Driven</span>
            <span><PvIcon name="lock" /> Private & Secure</span>
          </div>
        </article>

        <article class="pv-panel">
          <header class="pv-panel-header">
            <h2><span class="pv-flame"> </span>Trending Discussions</h2>
            <router-link to="/discussions" class="pv-small-button">View all</router-link>
          </header>
          <div class="pv-topic-list">
            <p v-if="discussionsLoaded && discussions.length === 0" class="pv-muted">No trending discussions yet.</p>
            <router-link v-for="topic in discussions.slice(0, 4)" :key="topic.title" :to="topic.href" class="pv-topic-row">
              <span class="pv-avatar" :class="topic.color">{{ topic.initial }}</span>
              <span class="pv-topic-main">
                <strong>{{ topic.title }}</strong>
                <small>{{ topic.excerpt }}</small>
                <em>{{ topic.author }} · {{ topic.time }}</em>
              </span>
              <span class="pv-stat"><strong>{{ topic.replies }}</strong><small>Replies</small></span>
              <span class="pv-stat"><strong>{{ topic.views }}</strong><small>Views</small></span>
            </router-link>
          </div>
        </article>

        <article class="pv-panel">
          <header class="pv-panel-header">
            <h2><PvIcon name="flask" /> Latest Lab Results</h2>
            <router-link to="/lab-results" class="pv-small-button">View all</router-link>
          </header>
          <div class="pv-lab-compact">
            <p v-if="labResultsLoaded && labResults.length === 0" class="pv-muted">No lab results published yet.</p>
            <router-link v-for="result in labResults.slice(0, 3)" :key="result.name" :to="result.href" class="pv-lab-row">
              <span class="pv-coa-thumb"></span>
              <span>
                <strong>{{ result.name }}</strong>
                <small>{{ result.type }} · {{ result.vendor }}</small>
                <em>Batch: {{ result.batch }} · {{ result.date }}</em>
              </span>
              <span class="pv-purity">{{ result.purity }}<small>Purity</small></span>
              <PvIcon name="chevron" />
            </router-link>
          </div>
        </article>
      </div>

      <aside class="pv-stack pv-right-rail">
        <article class="pv-panel">
          <header class="pv-panel-header">
            <h2><PvIcon name="megaphone" /> Announcements</h2>
            <router-link to="/announcements" class="pv-small-button">View all</router-link>
          </header>
          <div class="pv-mini-list">
            <p v-if="announcementsLoaded && announcementPreview.length === 0" class="pv-muted">No announcements published yet.</p>
            <router-link v-for="item in announcementPreview" :key="item.slug" :to="item.href" class="pv-mini-row">
              <span class="pv-icon-tile"><PvIcon :name="item.icon" /></span>
              <span><strong>{{ item.title }}</strong><small>{{ item.text }}</small><em>{{ item.time }}</em></span>
            </router-link>
          </div>
        </article>
        <article class="pv-panel">
          <header class="pv-panel-header"><h2><PvIcon name="users" /> Members Online</h2><span class="pv-count">{{ memberStats.online }}</span></header>
          <div class="pv-avatar-stack">
            <span v-for="member in onlineMembers" :key="member.name" class="pv-avatar" :class="member.color">{{ member.initial }}</span>
            <span class="pv-more">+{{ Math.max(0, memberStats.online - onlineMembers.length) }}</span>
          </div>
          <p class="pv-muted">Active members in the last 15 minutes</p>
        </article>
        <article class="pv-panel">
          <header class="pv-panel-header">
            <h2><PvIcon name="star" /> Top Reviewed Vendors</h2>
            <router-link to="/vendor-reviews" class="pv-small-button">View all</router-link>
          </header>
          <div class="pv-ranked-list">
            <p v-if="vendorsLoaded && vendors.length === 0" class="pv-muted">No reviewed vendors yet.</p>
            <router-link v-for="vendor in vendors.slice(0, 5)" :key="vendor.name" :to="vendor.href" class="pv-ranked-row">
              <span class="pv-vendor-logo">{{ vendor.logo }}</span>
              <span><strong>{{ vendor.name }}</strong><small class="pv-stars">★★★★★ <em>{{ vendor.rating }} ({{ vendor.reviews }})</em></small></span>
              <PvIcon name="chevron" />
            </router-link>
          </div>
        </article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'discussions'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div>
            <h1>Discussions</h1>
            <p>Share knowledge. Ask questions. Get real answers.</p>
          </div>
          <button class="pv-primary-button" @click="openNewDiscussion"><PvIcon name="plus" /> New Discussion</button>
        </header>
        <article class="pv-panel">
          <div class="pv-toolbar">
            <div class="pv-tabs">
              <button
                v-for="category in categoryFilters"
                :key="category.slug"
                :class="{ active: activeCategory === category.slug }"
                @click="setDiscussionCategory(category.slug)"
              >
                {{ category.name }}
              </button>
            </div>
            <button class="pv-small-button" type="button" @click="cycleDiscussionSort">{{ discussionSortLabel }} <PvIcon name="chevron" /></button>
            <button class="pv-icon-button" @click="applyDiscussionFilters"><PvIcon name="filter" /></button>
          </div>
          <form class="pv-inline-search" @submit.prevent="applyDiscussionFilters">
            <label class="pv-input-search">
              <input v-model="discussionSearch" placeholder="Search discussions..." type="search">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="discussionSearch || activeCategory" class="pv-small-button" type="button" @click="clearDiscussionFilters">Clear</button>
          </form>
          <p v-if="discussionStatusMessage" class="pv-alert pv-alert--compact">{{ discussionStatusMessage }}</p>
          <div class="pv-topic-list">
            <p v-if="discussionsLoaded && discussions.length === 0" class="pv-muted">No discussions found.</p>
            <router-link v-for="topic in discussions" :key="topic.title" :to="topic.href" class="pv-topic-row pv-topic-row--large">
              <span class="pv-avatar" :class="topic.color">{{ topic.initial }}</span>
              <span class="pv-topic-main">
                <strong>{{ topic.title }} <em class="pv-tag">{{ topic.tag }}</em></strong>
                <small>{{ topic.excerpt }}</small>
                <em>{{ topic.author }} · {{ topic.time }}</em>
              </span>
              <span class="pv-stat"><strong>{{ topic.replies }}</strong><small>Replies</small></span>
              <span class="pv-stat"><strong>{{ topic.views }}</strong><small>Views</small></span>
              <PvIcon name="bookmark" />
            </router-link>
          </div>
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <header class="pv-panel-header">
            <h2>Categories</h2>
            <button class="pv-small-button" @click="clearDiscussionFilters">View all</button>
          </header>
          <div class="pv-filter-list">
            <button
              v-for="category in categoryFilters"
              :key="category.slug"
              :class="{ active: activeCategory === category.slug }"
              @click="setDiscussionCategory(category.slug)"
            >
              <PvIcon :name="category.icon" />
              <i class="pv-filter-label">{{ category.name }}</i>
              <strong>{{ formatCount(category.count) }}</strong>
            </button>
          </div>
        </article>
        <PanelTrending />
        <article class="pv-panel">
          <header class="pv-panel-header"><h2><PvIcon name="chart" /> Community Stats</h2></header>
          <div class="pv-stat-grid">
            <span><strong>{{ formatCount(memberStats.total) }}</strong><small>Members</small></span>
            <span><strong>{{ formatCount(memberStats.online) }}</strong><small>Online</small></span>
            <span><strong>{{ formatCount(communityStats.total_discussions) }}</strong><small>Discussions</small></span>
            <span><strong>{{ formatCount(communityStats.total_replies) }}</strong><small>Messages</small></span>
          </div>
        </article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'discussionDetail'" class="pv-page">
    <div v-if="detailDiscussion" class="pv-content-grid pv-discussion-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs"><router-link to="/discussions">All Discussions</router-link> <PvIcon name="chevron" /> {{ detailCategoryLabel }} <PvIcon name="chevron" /> Topic</nav>
        <article class="pv-topic-detail pv-topic-detail--enhanced">
          <header class="pv-topic-hero-head">
            <div class="pv-topic-title-block">
              <div class="pv-topic-label-row">
                <span class="pv-tag">{{ detailCategoryLabel }}</span>
                <span><PvIcon name="message" /> {{ detailDiscussion.replies }} replies</span>
                <span><PvIcon name="eye" /> {{ detailDiscussion.views }} views</span>
              </div>
              <h1>{{ detailDiscussion.title }}</h1>
              <div class="pv-author-line">
                <span class="pv-avatar" :class="detailDiscussion.color">{{ detailDiscussion.initial }}</span>
                <strong>{{ detailDiscussion.author }}</strong>
                <span class="pv-tag">OP</span>
                <span>{{ detailDiscussion.time }}</span>
              </div>
            </div>
            <div class="pv-detail-actions">
              <button class="pv-icon-button pv-icon-button--static" aria-label="Share topic" @click="shareCurrentPage(detailDiscussion.title)"><PvIcon name="share" /></button>
              <button class="pv-primary-button" @click="toggleDiscussionFollow"><PvIcon name="shield" /> {{ isFollowingDiscussion ? 'Following' : 'Follow' }}</button>
            </div>
          </header>
          <div class="pv-topic-body">
            <p v-for="paragraph in detailParagraphs" :key="paragraph">{{ paragraph }}</p>
          </div>
          <div class="pv-action-row pv-topic-actions">
            <button class="pv-primary-button" @click="jumpToReplyComposer"><PvIcon name="message" /> Reply</button>
            <button class="pv-small-button" @click="toggleDiscussionFollow"><PvIcon name="star" /> {{ isFollowingDiscussion ? 'Unfollow' : 'Follow' }}</button>
            <span class="pv-flex-spacer"></span>
            <button class="pv-small-button" @click="toggleDiscussionSave"><PvIcon name="bookmark" /> {{ isSavedDiscussion ? 'Saved' : 'Save' }}</button>
            <button class="pv-small-button" @click="shareCurrentPage(detailDiscussion.title)"><PvIcon name="share" /> Share</button>
          </div>
        </article>
        <p v-if="actionStatusMessage" class="pv-alert pv-alert--compact">{{ actionStatusMessage }}</p>
        <div class="pv-thread-bar"><span>{{ replies.length }} replies · Sort by <strong>Latest activity</strong></span><a href="#reply-composer">Jump to reply</a></div>
        <article v-for="reply in replies" :key="`${reply.author}-${reply.time}`" class="pv-reply-card">
          <span class="pv-avatar" :class="reply.color">{{ reply.initial }}</span>
          <div class="pv-reply-main">
            <div class="pv-reply-head"><strong>{{ reply.author }}</strong><span class="pv-tag" v-if="reply.badge">{{ reply.badge }}</span><span>{{ reply.time }}</span></div>
            <p>{{ reply.text }}</p>
            <div v-if="reply.file" class="pv-file-card"><PvIcon name="document" /><span><strong>{{ reply.file }}</strong><small>245 KB · PDF</small></span><PvIcon name="download" /></div>
            <div class="pv-reply-actions"><button @click="prepareReply(reply)">Reply</button><button @click="prepareReply(reply, true)">Quote</button></div>
          </div>
          <button class="pv-upvote" :class="{ active: hasVotedReply(reply) }" @click="toggleReplyVote(reply)">↑ {{ replyVoteCount(reply) }}</button>
        </article>
        <p v-if="replyStatusMessage" class="pv-alert pv-alert--compact">{{ replyStatusMessage }}</p>
        <form id="reply-composer" class="pv-composer pv-composer--expanded" @submit.prevent="submitReply">
          <span class="pv-avatar purple">U</span>
          <div class="pv-composer-field">
            <textarea v-model="replyBody" placeholder="Write a reply..."></textarea>
            <div class="pv-composer-tools">
              <EmojiPicker v-model="replyBody" />
              <button type="submit" class="pv-primary-button" :disabled="submittingReply">
                {{ submittingReply ? 'Posting...' : 'Post Reply' }}
              </button>
            </div>
          </div>
        </form>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>About this discussion</h2>
          <dl class="pv-data-list">
            <div><dt>Category</dt><dd><span class="pv-tag">{{ detailCategoryLabel }}</span></dd></div>
            <div><dt>Replies</dt><dd>{{ detailDiscussion.replies }}</dd></div>
            <div><dt>Views</dt><dd>{{ detailDiscussion.views }}</dd></div>
            <div><dt>Created</dt><dd>{{ detailDiscussion.time }}</dd></div>
            <div><dt>Last activity</dt><dd>{{ detailDiscussion.lastActivity ?? detailDiscussion.time }}</dd></div>
          </dl>
        </article>
        <article class="pv-panel">
          <header class="pv-panel-header"><h2>Participants</h2><span class="pv-count">{{ discussionParticipants.length }}</span></header>
          <div class="pv-avatar-stack"><span v-for="member in discussionParticipants.slice(0, 5)" :key="member.name" class="pv-avatar" :class="member.color">{{ member.initial }}</span><span v-if="discussionParticipants.length > 5" class="pv-more">+{{ discussionParticipants.length - 5 }}</span></div>
          <router-link class="pv-small-button pv-full" to="/members"><PvIcon name="users" /> View all participants</router-link>
        </article>
        <article class="pv-panel">
          <h2>Similar topics</h2>
          <div class="pv-mini-list">
            <router-link v-for="topic in similarDiscussionTopics" :key="topic.title" :to="topic.href" class="pv-mini-row">
              <span class="pv-avatar" :class="topic.color">{{ topic.initial }}</span>
              <span><strong>{{ topic.title }}</strong><small>{{ topic.replies }} replies · {{ topic.views }} views</small></span>
            </router-link>
          </div>
          <router-link class="pv-small-button pv-full" to="/discussions"><PvIcon name="message" /> View more topics</router-link>
        </article>
      </aside>
    </div>
    <div v-else class="pv-empty-route">
      <h1>Discussion not found</h1>
      <p>This discussion has not been published or does not exist.</p>
    </div>
  </section>

  <section v-else-if="page === 'labResults'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Lab Results</h1><p>Independent lab testing and analysis from community members.</p></div>
          <button class="pv-primary-button" @click="openSubmitLabResult"><PvIcon name="flask" /> Submit Lab Result</button>
        </header>
        <div class="pv-metrics">
          <span v-for="metric in labMetricCards" :key="metric.label"><PvIcon :name="metric.icon" /><strong>{{ metric.value }}</strong><small>{{ metric.label }}</small></span>
        </div>
        <article class="pv-panel">
          <div class="pv-toolbar">
            <div class="pv-tabs">
              <button :class="{ active: labTypeFilter === '' }" @click="setLabTypeFilter('')">All Results</button>
              <button v-for="type in labFilterOptions.compound_types" :key="type" :class="{ active: labTypeFilter === type }" @click="setLabTypeFilter(type)">{{ type }}</button>
            </div>
            <button class="pv-small-button" type="button" @click="cycleLabSort">{{ labSortLabel }} <PvIcon name="chevron" /></button>
            <button class="pv-icon-button" @click="applyLabFilters"><PvIcon name="filter" /></button>
          </div>
          <form class="pv-inline-search" @submit.prevent="applyLabFilters">
            <label class="pv-input-search">
              <input v-model="labSearch" placeholder="Search compounds, vendors, batches, labs..." type="search">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="labHasActiveFilters" class="pv-small-button" type="button" @click="clearLabFilters">Clear</button>
          </form>
          <p v-if="labStatusMessage" class="pv-alert pv-alert--compact">{{ labStatusMessage }}</p>
          <p v-if="labResultsLoaded && labResults.length === 0" class="pv-muted">No lab results found.</p>
          <router-link v-for="result in labResults" :key="result.slug" :to="result.href" class="pv-result-row">
            <span class="pv-coa-thumb pv-coa-thumb--large"></span>
            <span class="pv-vial-icon" :class="result.color"><PvIcon name="flask" /></span>
            <span class="pv-topic-main">
              <strong>{{ result.name }}</strong>
              <small>Vendor: {{ result.vendor }}</small>
              <small>Batch: {{ result.batch }}</small>
              <small>Lab: <em>{{ result.lab }}</em></small>
              <span class="pv-chip-row"><span>{{ result.type }}</span><span>{{ result.use }}</span><span>{{ result.sampleType }}</span></span>
            </span>
            <span class="pv-purity pv-purity--green">{{ result.purity }}<small>Purity</small></span>
            <span class="pv-identity"><PvIcon name="shield" /> {{ result.identityResult }}<br>Identity</span>
            <span class="pv-date">{{ result.date }}<small>Tested Date</small></span>
            <span class="pv-small-button">View Report</span>
            <span class="pv-row-meta"><PvIcon name="eye" /> {{ result.views }} <PvIcon name="message" /> {{ result.comments }}</span>
          </router-link>
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filter Results</h2><button class="pv-purple-link" @click="clearLabFilters">Clear all</button></header>
          <label class="pv-input-search"><input v-model="labSearch" placeholder="Search compounds, vendors..." @keydown.enter="applyLabFilters"><PvIcon name="search" /></label>
          <label>Compound Type<select v-model="labTypeFilter"><option value="">All compound types</option><option v-for="type in labFilterOptions.compound_types" :key="type" :value="type">{{ type }}</option></select></label>
          <label>Compound<select v-model="labCompoundFilter"><option value="">All compounds</option><option v-for="compound in labFilterOptions.compounds" :key="compound" :value="compound">{{ compound }}</option></select></label>
          <label>Vendor<select v-model="labVendorFilter"><option value="">All vendors</option><option v-for="vendor in labFilterOptions.vendors" :key="vendor" :value="vendor">{{ vendor }}</option></select></label>
          <label>Lab<select v-model="labLabFilter"><option value="">All labs</option><option v-for="lab in labFilterOptions.labs" :key="lab" :value="lab">{{ lab }}</option></select></label>
          <button class="pv-primary-button pv-full" @click="applyLabFilters">Apply Filters</button>
        </article>
        <article class="pv-panel">
          <h2>About Lab Results</h2>
          <p class="pv-muted">All lab results are shared by community members for educational and harm reduction purposes only.</p>
          <ul class="pv-check-list"><li>Independent testing</li><li>Community verified</li><li>COA and analysis reports</li><li>Batch specific results</li></ul>
          <button class="pv-purple-link" type="button" @click="openSubmitLabResult">How to submit a lab result -></button>
        </article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'labReport'" class="pv-page">
    <div v-if="detailLabResult" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Lab Results <PvIcon name="chevron" /> {{ detailLabResult.name }} <PvIcon name="chevron" /> Report</nav>
        <header class="pv-page-header">
          <div><h1>{{ detailLabResult.name }} <span class="pv-success-pill">{{ detailLabResult.purity }} Purity</span></h1><p>Batch: {{ detailLabResult.batch }} · Vendor: <router-link :to="{ path: '/lab-results', query: { q: detailLabResult.vendor } }">{{ detailLabResult.vendor }}</router-link> · Lab: <router-link :to="{ path: '/lab-results', query: { q: detailLabResult.lab } }">{{ detailLabResult.lab }}</router-link></p></div>
          <div class="pv-action-row"><button class="pv-small-button" @click="downloadLabReport"><PvIcon name="download" /> Download Report</button><button class="pv-primary-button" @click="shareCurrentPage(detailLabResult.name)"><PvIcon name="share" /> Share</button></div>
        </header>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: labDetailTab === 'overview' }" @click="labDetailTab = 'overview'">Report Overview</button><button :class="{ active: labDetailTab === 'certificate' }" @click="labDetailTab = 'certificate'">Full Certificate</button><button :class="{ active: labDetailTab === 'raw' }" @click="labDetailTab = 'raw'">Raw Data</button><button :class="{ active: labDetailTab === 'batch' }" @click="labDetailTab = 'batch'">Batch History</button></div>
        <p v-if="labStatusMessage" class="pv-alert pv-alert--compact">{{ labStatusMessage }}</p>
        <div v-if="labDetailTab === 'overview'" class="pv-report-meta">
          <span><PvIcon name="calendar" /><small>Tested Date</small><strong>{{ detailLabResult.date }}</strong></span>
          <span><PvIcon name="box" /><small>Received Date</small><strong>{{ detailLabResult.receivedDate }}</strong></span>
          <span><PvIcon name="document" /><small>Report ID</small><strong>{{ detailLabResult.reportId }}</strong></span>
          <span><PvIcon name="flask" /><small>Sample Type</small><strong>{{ detailLabResult.sampleType }}</strong></span>
          <span><PvIcon name="shield" /><small>Sample Condition</small><strong>{{ detailLabResult.sampleCondition }}</strong></span>
        </div>
        <article v-if="labDetailTab === 'certificate' || labDetailTab === 'overview'" class="pv-certificate">
          <header><strong>{{ detailLabResult.lab }}</strong><span>Certificate of Analysis</span></header>
          <div class="pv-cert-grid">
            <span>Client <strong>{{ detailLabResult.vendor }}</strong></span><span>Analysis Date <strong>{{ detailLabResult.date }}</strong></span>
            <span>Sample <strong>{{ detailLabResult.name }}</strong></span><span>Report ID <strong>{{ detailLabResult.reportId }}</strong></span>
            <span>Batch <strong>{{ detailLabResult.batch }}</strong></span><span>Receipt Date <strong>{{ detailLabResult.receivedDate }}</strong></span>
          </div>
          <table>
            <thead><tr><th>Test</th><th>Result</th></tr></thead>
            <tbody>
              <tr v-if="detailLabResult.identityResult"><td>Identity</td><td>{{ detailLabResult.identityResult }}</td></tr>
              <tr v-if="detailLabResult.purity"><td>Purity</td><td class="pv-green-text">{{ detailLabResult.purity }}</td></tr>
              <tr v-if="detailLabResult.waterContent"><td>Water Content</td><td>{{ detailLabResult.waterContent }}</td></tr>
              <tr v-if="detailLabResult.peptideContent"><td>Peptide Content</td><td>{{ detailLabResult.peptideContent }}</td></tr>
            </tbody>
          </table>
        </article>
        <article v-if="labDetailTab === 'raw'" class="pv-panel"><h2>Raw Data</h2><dl class="pv-data-list"><div><dt>Identity</dt><dd>{{ detailLabResult.identityResult || 'Not provided' }}</dd></div><div><dt>Purity</dt><dd>{{ detailLabResult.purity || 'Not provided' }}</dd></div><div><dt>Water Content</dt><dd>{{ detailLabResult.waterContent || 'Not provided' }}</dd></div><div><dt>Peptide Content</dt><dd>{{ detailLabResult.peptideContent || 'Not provided' }}</dd></div><div><dt>COA Filename</dt><dd>{{ detailLabResult.coaFilename || 'Not attached' }}</dd></div></dl></article>
        <article v-if="labDetailTab === 'batch'" class="pv-panel"><h2>Batch History</h2><p class="pv-muted">Showing the submitted report for batch {{ detailLabResult.batch }}. Use the lab results search to find other records for this vendor, compound, or lab.</p><div class="pv-action-row"><button class="pv-small-button" @click="findCurrentBatchResults">Find this batch</button><button class="pv-small-button" @click="findCurrentVendorLabResults">Find vendor results</button></div></article>
        <article v-if="labDetailTab === 'overview'" class="pv-panel"><h2>Additional Notes</h2><p>{{ detailLabResult.notes }}</p></article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Summary</h2>
          <div class="pv-ring" :style="detailLabRingStyle"><strong>{{ detailLabResult.purity }}</strong><span>Purity</span></div>
          <dl class="pv-data-list"><div><dt>Identity</dt><dd>{{ detailLabResult.identityResult }}</dd></div><div><dt>Purity</dt><dd>{{ detailLabResult.purity }}</dd></div><div><dt>Water Content</dt><dd>{{ detailLabResult.waterContent }}</dd></div><div><dt>Peptide Content</dt><dd>{{ detailLabResult.peptideContent }}</dd></div></dl>
          <div class="pv-pass-box"><PvIcon name="shield" /> Overall Result <strong>{{ detailLabResult.overallResult }}</strong></div>
        </article>
        <article class="pv-panel"><h2>Compound Details</h2><dl class="pv-data-list"><div><dt>Compound Name</dt><dd>{{ detailLabResult.name }}</dd></div><div><dt>Type</dt><dd>{{ detailLabResult.type }}</dd></div><div><dt>Use Case</dt><dd>{{ detailLabResult.use }}</dd></div></dl></article>
        <article class="pv-panel"><h2>About This Report</h2><p class="pv-muted">This report is provided by {{ detailLabResult.lab }} for independent testing and analysis. All testing is performed using validated analytical methods.</p><ul class="pv-check-list"><li>ISO 17025 Accredited Lab</li><li>Community Funded</li><li>Independent & Unbiased</li><li>Batch Specific Results</li></ul></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route">
      <h1>Lab result not found</h1>
      <p>This report has not been published or does not exist.</p>
    </div>
  </section>

  <section v-else-if="page === 'vendorReviews'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header"><div><h1>Vendor Reviews</h1><p>Real reviews from real community members. Share your experience, help others make informed decisions.</p></div><router-link v-if="firstVendorReviewHref" :to="firstVendorReviewHref" class="pv-primary-button"><PvIcon name="plus" /> Write a Review</router-link></header>
        <div class="pv-metrics"><span v-for="metric in vendorMetricCards" :key="metric.label"><PvIcon :name="metric.icon" /><strong>{{ metric.value }}</strong><small>{{ metric.label }}</small></span></div>
        <article class="pv-panel">
          <div class="pv-toolbar"><div class="pv-tabs"><button :class="{ active: vendorStatusFilter === '' }" @click="setVendorStatusFilter('')">All Vendors</button><button v-for="status in vendorFilterOptions.statuses" :key="status.slug" :class="{ active: vendorStatusFilter === status.slug }" @click="setVendorStatusFilter(status.slug)">{{ status.name }}</button></div><button class="pv-small-button" type="button" @click="cycleVendorSort">{{ vendorSortLabel }} <PvIcon name="chevron" /></button><button class="pv-icon-button" @click="loadVendors"><PvIcon name="filter" /></button></div>
          <form class="pv-inline-search" @submit.prevent="loadVendors">
            <label class="pv-input-search">
              <input v-model="vendorSearch" placeholder="Search vendors..." type="search">
              <PvIcon name="search" />
            </label>
            <button class="pv-small-button" type="submit">Search</button>
            <button v-if="vendorHasActiveFilters" class="pv-small-button" type="button" @click="clearVendorFilters">Clear</button>
          </form>
          <p v-if="vendorStatusMessage" class="pv-alert pv-alert--compact">{{ vendorStatusMessage }}</p>
          <p v-if="vendorsLoaded && vendors.length === 0" class="pv-muted">No vendors found.</p>
          <router-link v-for="vendor in vendors" :key="vendor.slug" :to="vendor.href" class="pv-vendor-row">
            <span class="pv-logo-card" :class="vendor.class">{{ vendor.logoText }}</span>
            <span class="pv-topic-main"><strong>{{ vendor.name }} <em class="pv-tag" :class="vendor.statusClass">{{ vendor.status }}</em></strong><small><PvIcon name="star" /> {{ vendor.reviews }} reviews · Member since {{ vendor.since }}</small><span class="pv-chip-row"><span v-for="chip in vendor.chips" :key="chip">{{ chip }}</span></span></span>
            <span class="pv-rating" :class="vendor.tone"><PvIcon name="star" /> {{ vendor.rating }} / 5<small>Would buy again {{ vendor.buyAgain }}</small></span>
            <span class="pv-small-button">View Reviews</span>
          </router-link>
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Overall Rating</h2><div class="pv-big-rating">{{ vendorStats.average_rating }} <small>/5</small> <span class="pv-stars">★★★★★</span></div><p class="pv-muted">Based on {{ formatCount(vendorStats.total_reviews) }} reviews</p></article>
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filter Vendors</h2><button class="pv-text-button" type="button" @click="clearVendorFilters">Clear all</button></header>
          <label class="pv-input-search">
            <input v-model="vendorSearch" placeholder="Search vendors..." type="search" @keyup.enter="applyVendorFilters">
            <PvIcon name="search" />
          </label>
          <label>Rating<select v-model="vendorRatingFilter"><option value="">All ratings</option><option v-for="rating in vendorFilterOptions.ratings" :key="rating" :value="String(rating)">{{ rating }}+ stars</option></select></label>
          <label>Status<select v-model="vendorStatusFilter"><option value="">All statuses</option><option v-for="status in vendorFilterOptions.statuses" :key="status.slug" :value="status.slug">{{ status.name }}</option></select></label>
          <label>Tag<select v-model="vendorTagFilter"><option value="">All tags</option><option v-for="tag in vendorFilterOptions.tags" :key="tag" :value="tag">{{ tag }}</option></select></label>
          <button class="pv-primary-button pv-full" type="button" @click="applyVendorFilters">Apply Filters</button>
        </article>
        <article class="pv-panel"><header class="pv-panel-header"><h2>Top Vendors</h2><router-link to="/vendor-reviews" class="pv-purple-link">View all</router-link></header><div class="pv-ranked-list"><router-link v-for="(vendor, index) in topVendors" :key="vendor.name" :to="vendor.href" class="pv-ranked-row"><span class="pv-rank">{{ index + 1 }}</span><span class="pv-vendor-logo">{{ vendor.logo }}</span><strong>{{ vendor.name }}</strong><span class="pv-green-text"><PvIcon name="star" /> {{ vendor.rating }}</span></router-link></div></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'vendorDetail' || page === 'reviewModal'" class="pv-page">
    <div v-if="detailVendor" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Vendor Reviews <PvIcon name="chevron" /> {{ detailVendor.name }} <PvIcon name="chevron" /> Reviews</nav>
        <article class="pv-vendor-hero">
          <span class="pv-logo-card" :class="detailVendor.class">{{ detailVendor.logoText }}</span>
          <div class="pv-topic-main"><h1>{{ detailVendor.name }} <span class="pv-tag" :class="detailVendor.statusClass">{{ detailVendor.status }}</span></h1><p><PvIcon name="star" /> {{ detailVendor.rating }} / 5 · {{ detailVendor.reviews }} reviews</p><span class="pv-chip-row"><span v-for="chip in detailVendor.chips" :key="chip">{{ chip }}</span></span></div>
          <div class="pv-vendor-actions"><a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="pv-small-button">Visit Website <PvIcon name="share" /></a><small>Member since {{ detailVendor.since }}<br>Last active {{ detailVendor.lastActive }}</small></div>
        </article>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: vendorDetailTab === 'overview' }" @click="vendorDetailTab = 'overview'">Overview</button><button :class="{ active: vendorDetailTab === 'reviews' }" @click="vendorDetailTab = 'reviews'">Reviews ({{ detailVendor.reviews }})</button><button :class="{ active: vendorDetailTab === 'about' }" @click="vendorDetailTab = 'about'">About</button></div>
        <article v-if="vendorDetailTab === 'overview'" class="pv-panel pv-review-summary">
          <div class="pv-score-block"><strong>{{ detailVendor.rating }}</strong><span class="pv-stars">★★★★★</span><small>{{ detailVendor.reviews }} reviews</small></div>
          <div class="pv-bars pv-bars--wide"><span v-for="row in detailVendor.ratingDistribution" :key="row.rating">{{ row.rating }} stars <b :style="{ '--w': `${row.percent}%` }"></b><em>{{ row.percent }}% ({{ row.count }})</em></span></div>
          <div class="pv-rate-box"><h3>Rate this vendor</h3><p>Share your experience to help others in the community.</p><div class="pv-stars pv-stars--big">☆☆☆☆☆</div><router-link :to="`${detailVendor.href}/review`" class="pv-primary-button pv-full">Write a Review</router-link></div>
        </article>
        <template v-if="vendorDetailTab === 'reviews'">
          <div class="pv-toolbar">
            <label class="pv-compact-select">Rating<select v-model="vendorReviewRatingFilter"><option value="">All Ratings</option><option v-for="rating in [5, 4, 3, 2, 1]" :key="rating" :value="String(rating)">{{ rating }} stars</option></select></label>
            <label class="pv-compact-select">Product<select v-model="vendorReviewProductFilter"><option value="">All Products</option><option v-for="product in vendorReviewProductOptions" :key="product" :value="product">{{ product }}</option></select></label>
            <label class="pv-compact-select">When<select v-model="vendorReviewTimeFilter"><option value="all">All Time</option><option value="recent">Recent First</option></select></label>
            <span class="pv-flex-spacer"></span>
            <button class="pv-small-button" type="button" @click="toggleVendorReviewSort">{{ vendorReviewSortLabel }} <PvIcon name="chevron" /></button>
          </div>
          <p v-if="vendorReviewStatusMessage" class="pv-alert pv-alert--compact">{{ vendorReviewStatusMessage }}</p>
          <p v-if="reviews.length === 0" class="pv-muted">No published reviews yet.</p>
          <article v-for="review in reviews" :key="review.author" class="pv-review-row">
            <span class="pv-avatar" :class="review.color">{{ review.initial }}</span>
            <div>
              <div class="pv-reply-head"><strong>{{ review.author }}</strong><span class="pv-tag trusted">Verified Buyer</span><span>{{ review.date }}</span></div>
              <span class="pv-stars">★★★★★ <em>{{ review.rating }}/5</em></span>
              <h3>{{ review.title }}</h3>
              <p>{{ review.text }}</p>
              <span class="pv-chip-row"><span v-for="chip in review.chips" :key="chip">{{ chip }}</span></span>
            </div>
            <button class="pv-small-button" :disabled="markingReviewHelpful === review.id" @click="markReviewHelpful(review)"><PvIcon name="thumbs" /> Helpful ({{ review.helpful }})</button>
          </article>
        </template>
        <article v-if="vendorDetailTab === 'about'" class="pv-panel">
          <h2>About {{ detailVendor.name }}</h2>
          <p class="pv-muted">{{ detailVendor.description }}</p>
          <dl class="pv-data-list">
            <div><dt>Status</dt><dd><span class="pv-tag" :class="detailVendor.statusClass">{{ detailVendor.status }}</span></dd></div>
            <div><dt>Member Since</dt><dd>{{ detailVendor.since }}</dd></div>
            <div><dt>Last Active</dt><dd>{{ detailVendor.lastActive }}</dd></div>
            <div><dt>Response Rate</dt><dd>{{ detailVendor.responseRate }}</dd></div>
            <div><dt>Average Response</dt><dd>{{ detailVendor.avgResponseTime }}</dd></div>
          </dl>
          <a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="pv-small-button">Visit Website <PvIcon name="share" /></a>
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Vendor Summary</h2><dl class="pv-data-list"><div><dt>Total Reviews</dt><dd>{{ detailVendor.reviews }}</dd></div><div><dt>Average Rating</dt><dd><span class="pv-stars">★</span> {{ detailVendor.rating }} / 5</dd></div><div><dt>Would Buy Again</dt><dd>{{ detailVendor.buyAgain }}</dd></div><div><dt>Response Rate</dt><dd>{{ detailVendor.responseRate }}</dd></div><div><dt>Avg. Response Time</dt><dd>{{ detailVendor.avgResponseTime }}</dd></div></dl></article>
        <article class="pv-panel"><h2>Top Rated Products</h2><div class="pv-ranked-list"><span v-for="product in detailVendor.topProducts" :key="product.name" class="pv-ranked-row"><span class="pv-vendor-logo"><PvIcon name="flask" /></span><strong>{{ product.name }}</strong><span>{{ product.rating }} ★ ({{ product.reviews }})</span></span></div><button class="pv-small-button pv-full" @click="vendorReviewProductFilter = ''; jumpToVendorReviews()">View all products</button></article>
        <article class="pv-panel"><h2>Active Discussions</h2><div class="pv-mini-list"><router-link v-for="topic in discussions.slice(0, 3)" :key="topic.title" :to="topic.href" class="pv-mini-row"><PvIcon name="discussions" /><span><strong>{{ topic.title }}</strong></span><span>{{ topic.replies }}</span></router-link></div><router-link class="pv-small-button pv-full" to="/discussions">View all discussions</router-link></article>
        <article class="pv-panel"><h2>About {{ detailVendor.name }}</h2><p class="pv-muted">{{ detailVendor.description }}</p><a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="pv-small-button">Visit Website <PvIcon name="share" /></a></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route">
      <h1>Vendor not found</h1>
      <p>This vendor has not been published or does not exist.</p>
    </div>
    <div v-if="page === 'reviewModal'" class="pv-modal-backdrop">
      <form v-if="detailVendor" class="pv-modal" @submit.prevent="submitVendorReview">
        <header class="pv-panel-header"><div><h2>Write a Review</h2><p class="pv-muted">Share your experience to help others in the community.</p></div><router-link :to="detailVendor.href" class="pv-icon-button pv-icon-button--static"><PvIcon name="close" /></router-link></header>
        <p v-if="vendorReviewFormError" class="pv-alert pv-alert--compact">{{ vendorReviewFormError }}</p>
        <label>Overall Rating *<input v-model.number="newVendorReview.rating" type="number" min="1" max="5" required></label>
        <label>Review Title *<input v-model="newVendorReview.title" required maxlength="160" placeholder="Summarise your experience in a few words..."><small>{{ newVendorReview.title.length }}/160</small></label>
        <label>Your Review *
  <div class="pv-textarea-with-emoji">
    <textarea v-model="newVendorReview.body" required maxlength="4000" placeholder="Tell others about your experience with this vendor. Include details about product quality, shipping, customer service, packaging, etc."></textarea>
    <EmojiPicker v-model="newVendorReview.body" />
  </div>
  <small>{{ newVendorReview.body.length }}/4000</small></label>
        <label>Product<input v-model="newVendorReview.product_name" maxlength="120" placeholder="Product reviewed"></label>
        <label>Tags<input v-model="newVendorReview.tags" placeholder="Comma-separated review tags"></label>
        <div><strong>Would you buy from this vendor again?</strong><div class="pv-choice-row"><button type="button" :class="{ active: newVendorReview.would_buy_again }" @click="newVendorReview.would_buy_again = true"><PvIcon name="thumbs" /> Yes, I would</button><button type="button" :class="{ active: !newVendorReview.would_buy_again }" @click="newVendorReview.would_buy_again = false">No, I wouldn&apos;t</button></div></div>
        <label>Add Photos (Optional)<span class="pv-upload-box"><PvIcon name="image" /> Click to upload or drag and drop<br><small>PNG, JPG up to 5MB each (max 5 images)</small></span></label>
        <footer><router-link :to="detailVendor.href" class="pv-small-button">Cancel</router-link><button class="pv-primary-button" type="submit" :disabled="submittingVendorReview">{{ submittingVendorReview ? 'Submitting...' : 'Submit Review' }}</button></footer>
      </form>
    </div>
  </section>

  <section v-else-if="page === 'researchLibrary'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header"><div><h1>Research Library</h1><p>Explore research, studies, and educational resources on peptides and performance compounds.</p></div></header>
        <div class="pv-tabs"><button :class="{ active: activeResearchCategory === '' }" @click="activeResearchCategory = ''; loadResearchContent()">All</button><button v-for="category in contentCategories.research" :key="category.slug" :class="{ active: activeResearchCategory === category.slug }" @click="activeResearchCategory = category.slug; loadResearchContent()">{{ category.name }}</button></div>
        <div class="pv-toolbar"><label class="pv-input-search"><input v-model="researchSearch" placeholder="Search articles, compounds, topics..." @keydown.enter="loadResearchContent"><PvIcon name="search" /></label><button class="pv-small-button" @click="cycleResearchSort">{{ researchSortLabel }} <PvIcon name="chevron" /></button><span class="pv-icon-button active pv-mode-indicator" aria-label="Library view"><PvIcon name="library" /></span><button class="pv-icon-button pv-icon-button--static" @click="loadResearchContent"><PvIcon name="filter" /></button></div>
        <p v-if="contentStatusMessage" class="pv-form-error">{{ contentStatusMessage }}</p>
        <p v-if="contentLoaded && articles.length === 0" class="pv-muted">No research articles published yet.</p>
        <div class="pv-article-grid">
          <router-link v-for="(article, index) in articles" :key="article.title" :to="article.href" class="pv-article-card">
            <span class="pv-thumb" :style="thumbnailStyle(article.imageIndex || index)"></span>
            <span class="pv-tag">{{ article.tag }}</span>
            <h2>{{ article.title }}</h2>
            <p>{{ article.excerpt }}</p>
            <footer><span><PvIcon name="calendar" /> {{ article.date }}</span><span><PvIcon name="eye" /> {{ article.views }}</span></footer>
            <PvIcon name="bookmark" class="pv-bookmark" :class="{ active: isBookmarkedContent(article.slug) }" />
          </router-link>
        </div>
        <PaginationBlock />
      </main>
      <aside class="pv-stack">
        <article class="pv-panel pv-filter-panel">
          <header class="pv-panel-header"><h2>Filters</h2><button class="pv-text-button" type="button" @click="clearResearchFilters">Clear all</button></header>
          <label class="pv-input-search">
            <input v-model="researchSearch" placeholder="Search research library..." type="search" @keyup.enter="applyResearchFilters">
            <PvIcon name="search" />
          </label>
          <label>Category<select v-model="activeResearchCategory"><option value="">All categories</option><option v-for="category in contentFilterOptions.research.categories" :key="category.slug" :value="category.slug">{{ category.name }}</option></select></label>
          <label>Compound<select v-model="researchCompoundFilter"><option value="">All compounds</option><option v-for="compound in contentFilterOptions.research.compounds" :key="compound" :value="compound">{{ compound }}</option></select></label>
          <label>Tag<select v-model="researchTagFilter"><option value="">All tags</option><option v-for="tag in contentFilterOptions.research.tags" :key="tag" :value="tag">{{ tag }}</option></select></label>
          <label>Sort By<select v-model="researchSort"><option v-for="sort in contentFilterOptions.research.sorts" :key="sort.value" :value="sort.value">{{ sort.label }}</option></select></label>
          <label>Published From<input v-model="researchPublishedFrom" :min="contentFilterOptions.research.date_bounds?.from ?? undefined" :max="contentFilterOptions.research.date_bounds?.to ?? undefined" type="date"></label>
          <label>Published To<input v-model="researchPublishedTo" :min="contentFilterOptions.research.date_bounds?.from ?? undefined" :max="contentFilterOptions.research.date_bounds?.to ?? undefined" type="date"></label>
          <button class="pv-primary-button pv-full" type="button" @click="applyResearchFilters">Apply Filters</button>
        </article>
        <article class="pv-panel"><h2>Popular Topics</h2><dl class="pv-data-list"><div v-for="topic in popularTopics" :key="topic.name"><dt>{{ topic.name }}</dt><dd>{{ topic.count }}</dd></div></dl><button class="pv-purple-link" type="button" @click="clearResearchFilters">View all topics -></button></article>
        <article class="pv-panel"><h2>About the Library</h2><p class="pv-muted">Our research library is curated from credible sources and community contributions. Always do your own research and consult a professional.</p><router-link class="pv-purple-link" to="/guides">Submission Guidelines -></router-link></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'researchArticle'" class="pv-page">
    <div v-if="detailArticle" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Research Library <PvIcon name="chevron" /> {{ detailArticle.category }} <PvIcon name="chevron" /> {{ detailArticle.title }}</nav>
        <article class="pv-article-hero">
          <div><span class="pv-tag">{{ detailArticle.tag }}</span><h1>{{ detailArticle.title }}</h1><p>{{ detailArticle.excerpt }}</p><div class="pv-author-line"><span class="pv-avatar purple">{{ detailArticle.authorInitial }}</span><strong>{{ detailArticle.author }}</strong><span v-if="detailArticle.authorBadge" class="pv-tag trusted">{{ detailArticle.authorBadge }}</span><span>{{ detailArticle.date }}</span><span><PvIcon name="eye" /> {{ detailArticle.timeLabel }} read</span></div></div>
          <span class="pv-article-image" :style="thumbnailStyle(detailArticle.imageIndex)"></span>
        </article>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: researchDetailTab === 'article' }" @click="researchDetailTab = 'article'">Article</button><button :class="{ active: researchDetailTab === 'data' }" @click="researchDetailTab = 'data'">Figures & Data</button><button :class="{ active: researchDetailTab === 'references' }" @click="researchDetailTab = 'references'">References</button><button :class="{ active: researchDetailTab === 'comments' }" @click="researchDetailTab = 'comments'">Comments ({{ detailArticle.comments }})</button></div>
        <article v-if="researchDetailTab === 'article'" class="pv-prose">
          <template v-for="block in articleBodyBlocks" :key="block.text">
            <h2 v-if="block.kind === 'heading'">{{ block.text }}</h2>
            <ul v-else-if="block.kind === 'list'"><li v-for="item in block.items" :key="item">{{ item }}</li></ul>
            <p v-else>{{ block.text }}</p>
          </template>
        </article>
        <article v-else-if="researchDetailTab === 'data'" class="pv-panel"><h2>Figures & Data</h2><dl class="pv-data-list"><div v-for="(value, key) in detailArticle.metadata" :key="key"><dt>{{ formatMetadataKey(key) }}</dt><dd>{{ formatMetadataValue(value) }}</dd></div></dl></article>
        <article v-else-if="researchDetailTab === 'references'" class="pv-panel"><h2>References</h2><p class="pv-muted">{{ detailArticle.metadata.references ?? 'No references were attached to this article.' }}</p></article>
        <article v-else class="pv-panel"><h2>Comments</h2><p class="pv-muted">Discussion comments are not attached to this article yet. Start a related topic in the community discussions.</p><router-link to="/discussions" class="pv-primary-button">Open Discussions</router-link></article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Quick Info</h2><dl class="pv-data-list"><div><dt>Category</dt><dd>{{ detailArticle.category }}</dd></div><div><dt>Compound</dt><dd>{{ detailArticle.metadata.compound ?? detailArticle.title }}</dd></div><div><dt>Also Known As</dt><dd>{{ detailArticle.metadata.also_known_as ?? '' }}</dd></div><div><dt>Research Phase</dt><dd>{{ detailArticle.metadata.research_phase ?? '' }}</dd></div><div><dt>Last Updated</dt><dd>{{ detailArticle.date }}</dd></div><div><dt>Views</dt><dd>{{ detailArticle.views }}</dd></div><div><dt>Downloads</dt><dd>{{ detailArticle.downloads }}</dd></div></dl></article>
        <article class="pv-panel"><h2>Table of Contents</h2><ol class="pv-toc"><li v-for="heading in articleHeadings" :key="heading" :class="{ active: heading === articleHeadings[0] }">{{ heading }}</li></ol></article>
        <article class="pv-panel"><h2>Related Articles</h2><div class="pv-mini-list"><router-link v-for="(article, index) in articles.filter(item => item.slug !== detailArticle.slug).slice(0, 3)" :key="article.title" :to="article.href" class="pv-mini-row"><span class="pv-mini-thumb" :style="thumbnailStyle(article.imageIndex || index + 1)"></span><span><strong>{{ article.title }}</strong><small>{{ article.date }}</small></span></router-link></div><router-link to="/research-library" class="pv-primary-button pv-full">View all related articles</router-link></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route"><h1>Research article not found</h1><p>This article has not been published or does not exist.</p></div>
  </section>

  <section v-else-if="page === 'guides'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header"><div><h1>Guides & FAQ</h1><p>Helpful guides, tutorials and answers to common questions from the community.</p></div></header>
        <label class="pv-input-search pv-input-search--wide"><input v-model="guideSearch" placeholder="Search guides & FAQ..." @keydown.enter="loadGuidesContent"><PvIcon name="search" /></label>
        <div class="pv-guide-cats"><button :class="{ active: activeGuideCategory === '' }" @click="activeGuideCategory = ''; loadGuidesContent()"><PvIcon name="library" /><strong>All Topics</strong><small>View all</small></button><button v-for="category in contentCategories.guide" :key="category.slug" :class="{ active: activeGuideCategory === category.slug }" @click="activeGuideCategory = category.slug; loadGuidesContent()"><PvIcon name="document" /><strong>{{ category.name }}</strong><small>{{ category.count }} guides</small></button></div>
        <article class="pv-panel">
          <header class="pv-panel-header"><h2>Popular Guides</h2><router-link to="/guides" class="pv-purple-link">View all guides →</router-link></header>
          <p v-if="contentLoaded && guides.length === 0" class="pv-muted">No guides published yet.</p>
          <router-link v-for="(guide, index) in guides" :key="guide.title" :to="guide.href" class="pv-guide-row"><span class="pv-mini-thumb pv-guide-thumb" :style="thumbnailStyle(guide.imageIndex || index)"></span><span class="pv-topic-main"><strong>{{ guide.title }}</strong><small>{{ guide.excerpt }}</small><em>{{ guide.category }}</em></span><span><PvIcon name="document" /> {{ guide.metadata.guide_type ?? guide.type }}<br><PvIcon name="clock" /> {{ guide.timeLabel }} read<br><PvIcon name="eye" /> {{ guide.views }} views</span></router-link>
        </article>
        <article id="faq-list" class="pv-panel"><header class="pv-panel-header"><h2>Frequently Asked Questions</h2><a class="pv-purple-link" href="#faq-list">View all FAQ →</a></header><details v-for="faq in faqs" :key="faq.slug"><summary><PvIcon name="question" /> {{ faq.title }}</summary><p class="pv-muted">{{ faq.body || faq.excerpt }}</p></details></article>
      </main>
      <aside class="pv-stack"><article class="pv-panel pv-help-card"><PvIcon name="question" /><h2>Need Help?</h2><p>Can&apos;t find what you&apos;re looking for?</p><router-link to="/discussions" class="pv-primary-button pv-full">Ask a Question</router-link></article><article class="pv-panel"><h2>Top FAQ Topics</h2><dl class="pv-data-list"><div v-for="category in contentCategories.faq" :key="category.slug"><dt>{{ category.name }}</dt><dd>{{ category.count }}</dd></div></dl></article><article class="pv-panel"><h2>Community Help</h2><div class="pv-mini-list"><router-link to="/discussions" class="pv-mini-row"><PvIcon name="question" /><span><strong>Ask the Community</strong><small>Get answers from experienced members</small></span><PvIcon name="chevron" /></router-link><a href="/admin/community-content" class="pv-mini-row"><PvIcon name="document" /><span><strong>Submit a Guide</strong><small>Share your knowledge with others</small></span><PvIcon name="chevron" /></a><router-link to="/announcements" class="pv-mini-row"><PvIcon name="bell" /><span><strong>Report an Issue</strong><small>Report outdated or incorrect info</small></span><PvIcon name="chevron" /></router-link></div></article><article class="pv-panel"><h2>Popular Tags</h2><span class="pv-chip-row"><span v-for="topic in contentTopics.guide" :key="topic.name">{{ topic.name }}</span></span></article></aside>
    </div>
  </section>

  <section v-else-if="page === 'guideDetail'" class="pv-page">
    <div v-if="detailGuide" class="pv-content-grid">
      <main class="pv-stack">
        <nav class="pv-breadcrumbs">Guides & FAQ <PvIcon name="chevron" /> {{ detailGuide.category }} <PvIcon name="chevron" /></nav>
        <header class="pv-page-header"><div><h1>{{ detailGuide.title }}</h1><p>{{ detailGuide.excerpt }}</p><span class="pv-muted"><PvIcon name="calendar" /> {{ detailGuide.date }} · <PvIcon name="clock" /> {{ detailGuide.timeLabel }} read · <PvIcon name="eye" /> {{ detailGuide.views }} views</span></div><div class="pv-action-row"><button class="pv-small-button" @click="toggleContentBookmark(detailGuide.slug)"><PvIcon name="bookmark" /> {{ isBookmarkedContent(detailGuide.slug) ? 'Bookmarked' : 'Bookmark' }}</button><button class="pv-primary-button" @click="printCurrentPage"><PvIcon name="download" /> Print / PDF</button></div></header>
        <span class="pv-guide-hero" :style="thumbnailStyle(detailGuide.imageIndex)"></span>
        <article class="pv-alert"><PvIcon name="shield" /><strong>Disclaimer</strong><p>This guide is for educational purposes only and does not constitute medical advice. Always follow lab safety best practices and consult a healthcare professional for personal guidance.</p></article>
        <article class="pv-prose">
          <template v-for="block in guideBodyBlocks" :key="block.text">
            <h2 v-if="block.kind === 'heading'">{{ block.text }}</h2>
            <ul v-else-if="block.kind === 'list'"><li v-for="item in block.items" :key="item">{{ item }}</li></ul>
            <p v-else>{{ block.text }}</p>
          </template>
          <h2 v-if="detailGuideSteps.length">Step-by-Step Instructions</h2>
          <div v-if="detailGuideSteps.length" class="pv-step-list"><div v-for="(step, index) in detailGuideSteps" :key="step.title" class="pv-step"><span>{{ index + 1 }}</span><div><strong>{{ step.title }}</strong><p>{{ step.text }}</p></div><PvIcon :name="step.icon" /></div></div>
          <div class="pv-pass-box"><PvIcon name="shield" /> <span><strong>Safety First</strong><br>When in doubt, do not use the product. Your health and safety come first.</span></div>
        </article>
      </main>
      <aside class="pv-stack"><article class="pv-panel"><h2>On This Page</h2><ol class="pv-toc"><li v-for="heading in guideHeadings" :key="heading" :class="{ active: heading === guideHeadings[0] }">{{ heading }}</li></ol></article><article class="pv-panel"><h2>Quick Info</h2><dl class="pv-data-list"><div><dt>Difficulty</dt><dd><span class="pv-green-dot"></span> {{ detailGuide.metadata.difficulty ?? 'Beginner' }}</dd></div><div><dt>Time to Complete</dt><dd>{{ detailGuide.timeLabel }}</dd></div><div><dt>Category</dt><dd>{{ detailGuide.category }}</dd></div><div><dt>Last Updated</dt><dd>{{ detailGuide.date }}</dd></div></dl></article><article class="pv-panel"><h2>Related Guides</h2><div class="pv-mini-list"><router-link v-for="(guide, index) in guides.filter(item => item.slug !== detailGuide.slug).slice(0, 3)" :key="guide.title" :to="guide.href" class="pv-mini-row"><span class="pv-mini-thumb" :style="thumbnailStyle(guide.imageIndex || index + 1)"></span><span><strong>{{ guide.title }}</strong><small>{{ guide.timeLabel }} read</small></span></router-link></div></article><article class="pv-panel pv-help-card"><h2>Still Need Help?</h2><p>Ask the community or submit a question.</p><router-link to="/discussions" class="pv-primary-button pv-full">Ask a Question</router-link></article></aside>
    </div>
    <div v-else class="pv-empty-route"><h1>Guide not found</h1><p>This guide has not been published or does not exist.</p></div>
  </section>

  <section v-else-if="page === 'members'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header"><div><h1>Members</h1><p>Browse published community profiles and contributors.</p></div></header>
        <article class="pv-panel">
          <header class="pv-panel-header"><h2>Community Members</h2><span class="pv-tag">{{ memberStats.total }} total</span></header>
          <p v-if="membersLoaded && apiMembers.length === 0" class="pv-muted">No member profiles have been published yet.</p>
          <router-link v-for="member in apiMembers" :key="member.slug" :to="member.href" class="pv-action-card">
            <span class="pv-avatar" :class="member.color">{{ member.initial }}</span>
            <span><strong>{{ member.name }} <em v-if="member.isVerified" class="pv-tag trusted">Verified</em></strong><small>{{ member.bio }}</small><em>{{ member.group }} · {{ member.lastActive }}</em></span>
            <PvIcon name="chevron" />
          </router-link>
        </article>
      </main>
      <aside class="pv-stack"><article class="pv-panel"><h2>Member Stats</h2><dl class="pv-data-list"><div><dt>Total Members</dt><dd>{{ memberStats.total }}</dd></div><div><dt>Online Now</dt><dd>{{ memberStats.online }}</dd></div></dl></article><article class="pv-panel"><h2>Online Members</h2><div class="pv-avatar-stack"><router-link v-for="member in onlineMembers" :key="member.slug" :to="member.href" class="pv-avatar" :class="member.color">{{ member.initial }}</router-link></div></article></aside>
    </div>
  </section>

  <section v-else-if="page === 'memberDetail'" class="pv-page">
    <MemberProfile v-if="detailMember" :profile="detailMember" />
    <div v-else class="pv-empty-route"><h1>Member not found</h1><p>This member profile has not been published or does not exist.</p></div>
  </section>

  <section v-else-if="page === 'messages'" class="pv-page pv-messages-page">
    <div class="pv-messages">
      <aside class="pv-message-list">
        <header><h1>Messages</h1><span class="pv-icon-button active pv-mode-indicator" aria-label="Primary messages"><PvIcon name="document" /></span></header>
        <label class="pv-input-search"><input v-model="messageSearch" placeholder="Search messages..."><PvIcon name="search" /></label>
        <div class="pv-tabs pv-tabs--line"><span class="pv-tab-label active">Primary</span></div>
        <p v-if="messagesStatusMessage" class="pv-muted">{{ messagesStatusMessage }}</p>
        <p v-if="messagesLoaded && chats.length === 0" class="pv-muted">No message threads yet.</p>
        <button v-for="chat in chats" :key="chat.id" class="pv-chat-row" :class="{ active: currentThread?.id === chat.id }" @click="loadMessageThread(chat.id)"><span class="pv-avatar" :class="chat.participant.color">{{ chat.participant.initial }}</span><span><strong>{{ chat.participant.name }} <em v-if="chat.participant.role" class="pv-tag">{{ chat.participant.role }}</em></strong><small>{{ chat.preview }}</small></span><time>{{ chat.time }}</time></button>
      </aside>
      <main v-if="currentThread" class="pv-chat-panel">
        <header><router-link to="/messages" class="pv-icon-button pv-icon-button--static"><PvIcon name="chevron" /></router-link><span class="pv-avatar" :class="currentThread.participant.color">{{ currentThread.participant.initial }}</span><div><h2>{{ currentThread.participant.name }} <span class="pv-tag">{{ currentThread.participant.role }}</span></h2><small><span class="pv-green-dot"></span> {{ currentThread.participant.lastActive }}</small></div><span class="pv-flex-spacer"></span><router-link :to="currentThread.participant.href" class="pv-icon-button pv-icon-button--static" aria-label="View member profile"><PvIcon name="settings" /></router-link></header>
        <div v-if="showMessageSafetyNotice" class="pv-alert"><PvIcon name="shield" /><span>Messages are visible only to you and the recipient. Do not share personal info or make any transactions.</span><button type="button" class="pv-icon-button" aria-label="Dismiss notice" @click="showMessageSafetyNotice = false"><PvIcon name="close" /></button></div>
        <div class="pv-chat-stream"><span class="pv-date-sep">Messages</span><MessageBubble v-for="message in currentThread.messages" :key="message.id ?? message.time" :side="message.side" :text="message.text" :time="message.time" :file="Boolean(message.attachmentName)" :attachment-name="message.attachmentName ?? ''" :attachment-label="message.attachmentLabel" :avatar-initial="message.avatarInitial || currentThread.participant.initial" :avatar-color="message.avatarColor || currentThread.participant.color" /></div>
        <form class="pv-chat-input" @submit.prevent="sendMessage"><PvIcon name="share" /><input v-model="messageBody" placeholder="Type a message..."><PvIcon name="question" /><button class="pv-primary-button" :disabled="sendingMessage"><PvIcon name="send" /></button></form>
      </main>
      <main v-else class="pv-chat-panel"><article class="pv-panel"><h2>No thread selected</h2><p class="pv-muted">Choose a thread to read messages.</p></article></main>
    </div>
  </section>

  <section v-else-if="page === 'announcements'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div><h1>Announcements</h1><p>Official updates and important announcements from the Peptide Vendors team.</p></div>
          <a href="/admin/community-announcements" class="pv-primary-button"><PvIcon name="plus" /> New Announcement</a>
        </header>
        <div class="pv-tabs pv-tabs--line">
          <button :class="{ active: announcementFilter === 'all' }" @click="setAnnouncementFilter('all')">All Announcements</button>
          <button :class="{ active: announcementFilter === 'pinned' }" @click="setAnnouncementFilter('pinned')">Pinned</button>
          <button
            v-for="category in announcementCategories"
            :key="category.slug"
            :class="{ active: announcementFilter === category.slug }"
            @click="setAnnouncementFilter(category.slug)"
          >
            {{ category.name }}
          </button>
        </div>
        <p v-if="announcementStatusMessage" class="pv-form-error">{{ announcementStatusMessage }}</p>
        <p v-if="announcementsLoaded && announcements.length === 0" class="pv-muted">No announcements published yet.</p>
        <router-link v-for="announcement in announcements" :key="announcement.slug" :to="announcement.href" class="pv-announcement-row" :class="{ pinned: announcement.pinned }">
          <span class="pv-large-icon" :class="announcement.tone"><PvIcon :name="announcement.icon" /></span>
          <div>
            <span v-if="announcement.pinned" class="pv-tag">PINNED</span>
            <small :class="announcement.tone">{{ announcement.category }}</small>
            <h2>{{ announcement.title }}</h2>
            <p>{{ announcement.text }}</p>
            <div class="pv-author-line"><span class="pv-avatar purple">{{ announcement.authorInitial }}</span><strong>{{ announcement.author }}</strong><span class="pv-tag">Administrator</span><span>{{ announcement.date }}</span><span><PvIcon name="message" /> {{ announcement.comments }}</span><span><PvIcon name="eye" /> {{ announcement.views }}</span></div>
          </div>
          <span class="pv-icon-button pv-icon-button--static"><PvIcon name="chevron" /></span>
        </router-link>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>About Announcements</h2><span class="pv-icon-tile"><PvIcon name="send" /></span><p>This is where we share official updates, important notices, and community announcements.</p><ul class="pv-check-list"><li>Official updates from admins</li><li>Important safety information</li><li>Platform changes & maintenance</li><li>Community news & events</li></ul></article>
        <article class="pv-panel"><h2>Announcements Overview</h2><dl class="pv-data-list"><div><dt>Total Announcements</dt><dd>{{ announcementStats.total }}</dd></div><div><dt>Pinned</dt><dd>{{ announcementStats.pinned }}</dd></div><div><dt>This Month</dt><dd>{{ announcementStats.this_month }}</dd></div><div><dt>Total Views</dt><dd>{{ formatCount(announcementStats.total_views) }}</dd></div><div><dt>Total Comments</dt><dd>{{ announcementStats.total_comments }}</dd></div></dl></article>
        <article class="pv-panel"><h2>Recent Categories</h2><dl class="pv-data-list"><div v-for="category in announcementCategories" :key="category.slug"><dt><span class="pv-dot" :class="category.tone"></span>{{ category.name }}</dt><dd>{{ category.count }}</dd></div></dl></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'announcementNew'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack"><nav class="pv-breadcrumbs">Announcements <PvIcon name="chevron" /> Admin</nav><header class="pv-page-header"><div><h1>Create New Announcement</h1><p>Announcements are created and published through the existing admin panel.</p></div></header><article class="pv-form-card"><h2>Use the admin panel</h2><p>The public website reads announcements from the database. Staff can create drafts, publish updates, pin important notices, and hide old posts in the admin area.</p><a href="/admin/community-announcements" class="pv-primary-button"><PvIcon name="send" /> Open Announcement Manager</a></article></main>
      <aside class="pv-stack"><article class="pv-panel"><h2>Announcement Flow</h2><div class="pv-mini-list"><span class="pv-mini-row"><PvIcon name="document" /><span><strong>Create or edit</strong><small>Use the admin form to write the content.</small></span></span><span class="pv-mini-row"><PvIcon name="shield" /><span><strong>Publish safely</strong><small>Only published rows appear on the public site.</small></span></span><span class="pv-mini-row"><PvIcon name="star" /><span><strong>Pin key updates</strong><small>Pinned announcements stay at the top.</small></span></span></div></article></aside>
    </div>
  </section>

  <section v-else-if="page === 'announcementDetail'" class="pv-page">
    <div class="pv-content-grid">
      <main v-if="detailAnnouncement" class="pv-stack">
        <nav class="pv-breadcrumbs">Announcements <PvIcon name="chevron" /> {{ detailAnnouncement.category }} <PvIcon name="chevron" /></nav>
        <header class="pv-page-header">
          <div>
            <span class="pv-tag" :class="detailAnnouncement.tone">{{ detailAnnouncement.category }}</span>
            <h1>{{ detailAnnouncement.title }}</h1>
            <p>{{ detailAnnouncement.text }}</p>
            <span class="pv-muted"><PvIcon name="calendar" /> {{ detailAnnouncement.date }} · <PvIcon name="eye" /> {{ detailAnnouncement.views }} views</span>
          </div>
          <router-link to="/announcements" class="pv-small-button"><PvIcon name="chevron" /> All Announcements</router-link>
        </header>
        <article class="pv-notification-hero">
          <span class="pv-large-icon" :class="detailAnnouncement.tone"><PvIcon :name="detailAnnouncement.icon" /></span>
          <div><div class="pv-author-line"><span class="pv-avatar purple">{{ detailAnnouncement.authorInitial }}</span><strong>{{ detailAnnouncement.author }}</strong><span class="pv-tag">Administrator</span><span>{{ detailAnnouncement.time }}</span></div><h2>{{ detailAnnouncement.title }}</h2><p>{{ detailAnnouncement.text }}</p></div>
        </article>
        <article class="pv-panel pv-prose">
          <p v-for="paragraph in announcementParagraphs" :key="paragraph">{{ paragraph }}</p>
        </article>
      </main>
      <main v-else class="pv-stack"><router-link to="/announcements" class="pv-purple-link">Back to announcements</router-link><article class="pv-panel"><h1>Announcement not found</h1><p class="pv-muted">This announcement may have been unpublished.</p></article></main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Announcement Details</h2><dl class="pv-data-list"><div><dt>Category</dt><dd>{{ detailAnnouncement?.category ?? '' }}</dd></div><div><dt>Published</dt><dd>{{ detailAnnouncement?.date ?? '' }}</dd></div><div><dt>Views</dt><dd>{{ detailAnnouncement?.views ?? '0' }}</dd></div><div><dt>Comments</dt><dd>{{ detailAnnouncement?.comments ?? 0 }}</dd></div></dl></article>
        <article class="pv-panel"><h2>Related Links</h2><div class="pv-filter-list"><router-link to="/announcements"><PvIcon name="megaphone" /> Announcements <PvIcon name="chevron" /></router-link><router-link to="/discussions"><PvIcon name="message" /> Community Discussions <PvIcon name="chevron" /></router-link><router-link to="/lab-results"><PvIcon name="flask" /> Lab Results <PvIcon name="chevron" /></router-link></div></article>
      </aside>
    </div>
  </section>

  <section v-else-if="page === 'notifications'" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack"><header class="pv-page-header"><div><h1>Notifications</h1></div><button v-if="authStore.isAuthenticated" class="pv-small-button" :disabled="markingNotificationsRead" @click="markAllNotificationsRead">Mark all as read</button><router-link to="/settings/notifications" class="pv-icon-button"><PvIcon name="settings" /></router-link></header><div class="pv-tabs pv-tabs--line"><button :class="{ active: activeNotificationFilter === 'all' }" @click="setNotificationFilter('all')">All</button><button :class="{ active: activeNotificationFilter === 'unread' }" @click="setNotificationFilter('unread')">Unread <span>{{ notificationCounts.unread }}</span></button><button :class="{ active: activeNotificationFilter === 'announcements' }" @click="setNotificationFilter('announcements')">Announcements <span>{{ notificationCounts.announcements }}</span></button><button :class="{ active: activeNotificationFilter === 'lab-results' }" @click="setNotificationFilter('lab-results')">Lab Results <span>{{ notificationCounts.labResults }}</span></button><button :class="{ active: activeNotificationFilter === 'discussions' }" @click="setNotificationFilter('discussions')">Discussions <span>{{ notificationCounts.discussions }}</span></button><button :class="{ active: activeNotificationFilter === 'vendor-reviews' }" @click="setNotificationFilter('vendor-reviews')">Vendors <span>{{ notificationCounts.vendors }}</span></button></div><article class="pv-panel pv-notification-list"><p v-if="notificationStatusMessage" class="pv-muted">{{ notificationStatusMessage }}</p><p v-if="notificationsLoaded && notifications.length === 0" class="pv-muted">No notifications yet.</p><router-link v-for="item in notifications" :key="item.slug" :to="item.detailHref" class="pv-notification-row"><span class="pv-large-icon" :class="item.tone"><PvIcon :name="item.icon" /></span><span><strong>{{ item.title }}</strong><small>{{ item.text }}</small><em class="pv-tag" :class="item.tone">{{ item.category }}</em></span><time>{{ item.time }}</time><b v-if="item.unread"></b></router-link></article><PaginationBlock :label="notificationPaginationLabel" /></main>
      <aside class="pv-stack"><article class="pv-panel"><h2>Filter Notifications</h2><div class="pv-filter-list"><button :class="{ active: activeNotificationFilter === 'all' }" @click="setNotificationFilter('all')"><PvIcon name="bell" /> All Notifications <strong>{{ notificationCounts.total }}</strong></button><button :class="{ active: activeNotificationFilter === 'unread' }" @click="setNotificationFilter('unread')"><PvIcon name="clock" /> Unread <strong>{{ notificationCounts.unread }}</strong></button><button :class="{ active: activeNotificationFilter === 'announcements' }" @click="setNotificationFilter('announcements')"><PvIcon name="megaphone" /> Announcements <strong>{{ notificationCounts.announcements }}</strong></button><button :class="{ active: activeNotificationFilter === 'lab-results' }" @click="setNotificationFilter('lab-results')"><PvIcon name="flask" /> Lab Results <strong>{{ notificationCounts.labResults }}</strong></button><button :class="{ active: activeNotificationFilter === 'discussions' }" @click="setNotificationFilter('discussions')"><PvIcon name="message" /> Discussions <strong>{{ notificationCounts.discussions }}</strong></button><button :class="{ active: activeNotificationFilter === 'vendor-reviews' }" @click="setNotificationFilter('vendor-reviews')"><PvIcon name="star" /> Vendors <strong>{{ notificationCounts.vendors }}</strong></button></div></article><article class="pv-panel"><h2>Notification Summary</h2><div class="pv-mini-list"><span v-for="item in notificationSummary" :key="item.label" class="pv-mini-row"><PvIcon :name="item.icon" /><span><strong>{{ item.label }}</strong><small>{{ item.latest }}</small></span><strong>{{ item.count }}</strong></span></div></article><router-link to="/settings/notifications" class="pv-panel pv-settings-link"><PvIcon name="settings" /> View Notification Settings <PvIcon name="chevron" /></router-link></aside>
    </div>
  </section>

  <section v-else-if="page === 'notificationDetail'" class="pv-page">
    <div class="pv-content-grid">
      <main v-if="primaryNotification" class="pv-stack"><router-link to="/notifications" class="pv-purple-link">Back</router-link><header class="pv-page-header"><div><h1>Notification</h1><router-link to="/notifications" class="pv-purple-link">Back to all notifications</router-link></div><button v-if="authStore.isAuthenticated && primaryNotification.unread" class="pv-small-button" :disabled="markingNotificationsRead" @click="markNotificationRead(primaryNotification.slug)"><PvIcon name="mail" /> Mark as read</button><router-link v-if="previousNotification" class="pv-icon-button pv-icon-button--static" :to="previousNotification.detailHref" aria-label="Previous notification"><PvIcon name="chevron" /></router-link><router-link v-if="nextNotification" class="pv-icon-button pv-icon-button--static" :to="nextNotification.detailHref" aria-label="Next notification"><PvIcon name="chevron" /></router-link></header><article class="pv-notification-hero"><span class="pv-large-icon" :class="primaryNotification.tone"><PvIcon :name="primaryNotification.icon" /></span><div><div class="pv-author-line"><span class="pv-tag">{{ primaryNotification.category }}</span><strong>{{ primaryNotification.author }}</strong><span>{{ primaryNotification.time }}</span></div><h2>{{ primaryNotification.title }}</h2><p>{{ primaryNotification.text }}</p><router-link :to="primaryNotification.href" class="pv-primary-button">Open {{ primaryNotification.category }} <PvIcon name="share" /></router-link></div></article><article class="pv-panel pv-prose"><h2>Full Message</h2><p v-for="paragraph in primaryNotification.bodyParagraphs" :key="paragraph">{{ paragraph }}</p><hr><h2>What you can do</h2><router-link :to="primaryNotification.href" class="pv-action-card"><PvIcon :name="primaryNotification.icon" /><span><strong>Open related content</strong><small>View the full source item for this notification.</small></span><PvIcon name="chevron" /></router-link><router-link to="/discussions" class="pv-action-card"><PvIcon name="message" /><span><strong>Join the discussion</strong><small>Discuss updates with the community.</small></span><PvIcon name="chevron" /></router-link></article></main>
      <main v-else class="pv-stack"><router-link to="/notifications" class="pv-purple-link">Back</router-link><article class="pv-panel"><h1>Notification not found</h1><p class="pv-muted">There are no live notifications to show yet.</p></article></main>
      <aside class="pv-stack"><article class="pv-panel"><h2>Notification Details</h2><dl class="pv-data-list"><div><dt>Type</dt><dd>{{ primaryNotification?.category ?? '' }}</dd></div><div><dt>From</dt><dd>{{ primaryNotification?.author ?? '' }}</dd></div><div><dt>Received</dt><dd>{{ primaryNotification?.time ?? '' }}</dd></div><div><dt>Status</dt><dd><span class="pv-dot purple"></span> {{ primaryNotification?.unread ? 'Unread' : 'Read' }}</dd></div></dl><hr><h2>Related Links</h2><div class="pv-filter-list"><router-link to="/lab-results"><PvIcon name="flask" /> Lab Results <PvIcon name="chevron" /></router-link><router-link to="/announcements"><PvIcon name="megaphone" /> Announcements <PvIcon name="chevron" /></router-link><router-link to="/discussions"><PvIcon name="message" /> Community Discussions <PvIcon name="chevron" /></router-link></div></article></aside>
    </div>
  </section>

  <section v-else-if="page.startsWith('settings')" class="pv-page">
    <SettingsScreens :page="page" />
  </section>

  <section v-else-if="page.startsWith('legal')" class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <article class="pv-panel pv-prose">
          <router-link to="/login" class="pv-purple-link">Back to sign in</router-link>
          <h1>{{ legalPage.title }}</h1>
          <p v-for="paragraph in legalPage.paragraphs" :key="paragraph">{{ paragraph }}</p>
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Community Documents</h2>
          <div class="pv-filter-list">
            <router-link to="/terms"><PvIcon name="document" /> Terms of Service <PvIcon name="chevron" /></router-link>
            <router-link to="/privacy"><PvIcon name="lock" /> Privacy Policy <PvIcon name="chevron" /></router-link>
            <router-link to="/community-rules"><PvIcon name="shield" /> Community Rules <PvIcon name="chevron" /></router-link>
          </div>
        </article>
      </aside>
    </div>
  </section>

  <section v-else class="pv-page">
    <div class="pv-empty-route">
      <h1>{{ fallbackTitle }}</h1>
      <p>The requested page does not exist.</p>
    </div>
  </section>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, onMounted, ref, watch, type PropType } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import PvIcon from '@/components/peptide/PvIcon.vue'
import EmojiPicker from '@/components/ui/EmojiPicker.vue'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import heroImage from '@/assets/peptide/hero-vials.png'
import researchImage from '@/assets/peptide/research-thumbnails.png'

const route = useRoute()
const router = useRouter()
const authStore = useAuthStore()
const page = computed(() => String(route.meta.page ?? 'home'))
const fallbackTitle = computed(() => String(route.meta.title ?? ''))

interface UiDiscussion {
  title: string
  excerpt: string
  author: string
  time: string
  replies: number
  views: string
  initial: string
  color: string
  tag: string
  href: string
  slug?: string
  body?: string
  category?: string
  lastActivity?: string
}

interface UiReply {
  author: string
  initial: string
  color: string
  badge?: string
  time: string
  text: string
  votes: number
  file?: string
}

interface ApiDiscussion {
  id?: number
  title: string
  slug: string
  tag?: string
  excerpt?: string | null
  body?: string | null
  replies?: number
  views?: number
  href?: string
  time_ago?: string | null
  last_activity?: string | null
  category?: { name?: string | null; color?: string | null } | null
  author?: { name?: string | null; initial?: string | null } | null
  reply_items?: ApiReply[]
}

interface ApiCategory {
  id?: number
  name: string
  slug: string
  icon: string
  color?: string
  count: number
}

interface ApiReply {
  body?: string | null
  attachment_name?: string | null
  votes?: number
  time_ago?: string | null
  author?: { name?: string | null; initial?: string | null; badge?: string | null } | null
}

interface DiscussionIndexResponse {
  data: ApiDiscussion[]
  meta?: {
    categories?: ApiCategory[]
  }
}

interface DiscussionDetailResponse {
  data: ApiDiscussion
}

interface UiLabResult {
  id?: number
  name: string
  slug: string
  type: string
  use: string
  vendor: string
  batch: string
  lab: string
  date: string
  receivedDate: string
  reportId: string
  sampleType: string
  sampleCondition: string
  purity: string
  purityPercent?: number | null
  waterContent: string
  peptideContent: string
  identityResult: string
  overallResult: string
  coaFilename?: string | null
  notes: string
  views: string
  comments: number
  color: string
  href: string
  submittedBy?: string
  status?: string
  isVerified?: boolean
}

interface ApiLabResult {
  id?: number
  compound_name: string
  slug: string
  compound_type?: string | null
  use_case?: string | null
  vendor_name: string
  batch_code: string
  lab_name: string
  tested_at?: string | null
  tested_date?: string | null
  received_at?: string | null
  received_date?: string | null
  report_id?: string | null
  sample_type?: string | null
  sample_condition?: string | null
  purity_percent?: number | null
  purity?: string | null
  water_content_percent?: number | null
  peptide_content_percent?: number | null
  identity_result?: string | null
  overall_result?: string | null
  coa_filename?: string | null
  notes?: string | null
  status?: string | null
  is_verified?: boolean
  views?: number
  comments?: number
  href?: string
  submitted_by?: { name?: string | null } | null
}

interface LabStats {
  total?: number
  batches?: number
  avg_purity?: number
  labs?: number
}

interface LabFilterOptions {
  compound_types: string[]
  compounds: string[]
  vendors: string[]
  labs: string[]
}

interface LabResultIndexResponse {
  data: ApiLabResult[]
  meta?: {
    stats?: LabStats
    filters?: Partial<LabFilterOptions>
  }
}

interface LabResultDetailResponse {
  data: ApiLabResult
}

interface RatingDistributionRow {
  rating: number
  count: number
  percent: number
}

interface VendorProduct {
  name: string
  rating: number
  reviews: number
}

interface UiVendor {
  id?: number
  name: string
  slug: string
  logo: string
  logoText: string
  class: string
  status: string
  statusClass: string
  rating: string
  reviews: number
  since: string
  buyAgain: string
  chips: string[]
  tone: string
  href: string
  description: string
  websiteUrl?: string | null
  lastActive: string
  responseRate: string
  avgResponseTime: string
  topProducts: VendorProduct[]
  ratingDistribution: RatingDistributionRow[]
}

interface UiVendorReview {
  id?: number
  author: string
  initial: string
  color: string
  date: string
  rating: number
  title: string
  text: string
  productName?: string | null
  chips: string[]
  helpful: number
}

interface ApiVendorReview {
  id?: number
  title: string
  body: string
  rating: number
  product_name?: string | null
  helpful_count?: number
  is_verified_buyer?: boolean
  tags?: string[]
  status?: string
  reviewed_date?: string | null
  author?: { name?: string | null; initial?: string | null } | null
}

interface ApiVendor {
  id?: number
  name: string
  slug: string
  logo_initials?: string | null
  logo_text?: string | null
  logo_class?: string | null
  status_label?: string | null
  status_class?: string | null
  tone?: string | null
  description?: string | null
  website_url?: string | null
  member_since_label?: string | null
  last_active_label?: string | null
  review_count?: number
  rating_label?: string | null
  average_rating?: number
  would_buy_again_label?: string | null
  response_rate_label?: string | null
  avg_response_time?: string | null
  tags?: string[]
  top_products?: VendorProduct[]
  href?: string
  rating_distribution?: RatingDistributionRow[]
  review_items?: ApiVendorReview[]
}

interface VendorStats {
  vendors_reviewed: number
  total_reviews: number
  average_rating: number
  would_buy_again: number
}

interface VendorFilterStatus {
  slug: string
  name: string
  count: number
}

interface VendorFilterOptions {
  statuses: VendorFilterStatus[]
  ratings: number[]
  tags: string[]
}

interface VendorIndexResponse {
  data: ApiVendor[]
  meta?: {
    stats?: VendorStats
    top_vendors?: ApiVendor[]
    filters?: Partial<VendorFilterOptions>
  }
}

interface VendorDetailResponse {
  data: ApiVendor
}

interface UiAnnouncement {
  id?: number
  title: string
  slug: string
  category: string
  icon: string
  tone: string
  text: string
  body: string
  date: string
  time: string
  comments: number
  views: string
  pinned: boolean
  href: string
  author: string
  authorInitial: string
}

interface ApiAnnouncement {
  id?: number
  title: string
  slug: string
  category: string
  category_slug: string
  icon?: string | null
  tone?: string | null
  excerpt?: string | null
  body?: string | null
  comments?: number
  views?: number
  is_pinned?: boolean
  status?: string
  published_at?: string | null
  published_label?: string | null
  time_ago?: string | null
  href?: string
  author?: { name?: string | null; initial?: string | null } | null
}

interface AnnouncementCategory {
  name: string
  slug: string
  tone: string
  count: number
}

interface AnnouncementStats {
  total: number
  pinned: number
  this_month: number
  total_views: number
  total_comments: number
}

interface AnnouncementIndexResponse {
  data: ApiAnnouncement[]
  meta?: {
    stats?: AnnouncementStats
    categories?: AnnouncementCategory[]
  }
}

interface AnnouncementDetailResponse {
  data: ApiAnnouncement
}

interface UiContentItem {
  id?: number
  type: 'research' | 'guide' | 'faq'
  title: string
  slug: string
  tag: string
  category: string
  excerpt: string
  body: string
  date: string
  time: string
  views: string
  downloads: string
  comments: number
  readMinutes: number
  timeLabel: string
  href: string
  imageIndex: number
  author: string
  authorInitial: string
  authorBadge?: string | null
  metadata: Record<string, any>
}

interface ApiContentItem {
  id?: number
  type: 'research' | 'guide' | 'faq'
  title: string
  slug: string
  category: string
  category_slug: string
  tag?: string | null
  excerpt?: string | null
  body?: string | null
  image_index?: number
  read_minutes?: number
  read_label?: string | null
  views?: number
  downloads?: number
  comments?: number
  metadata?: Record<string, any> | null
  published_label?: string | null
  time_ago?: string | null
  href?: string
  author?: { name?: string | null; initial?: string | null; badge?: string | null } | null
}

interface ContentCategory {
  name: string
  slug: string
  count: number
}

interface ContentTopic {
  name: string
  count: number
}

interface ContentSortOption {
  value: string
  label: string
}

interface ContentFilterOptions {
  categories: ContentCategory[]
  tags: string[]
  compounds: string[]
  sorts: ContentSortOption[]
  date_bounds?: {
    from?: string | null
    to?: string | null
  }
}

interface ContentIndexResponse {
  data: ApiContentItem[]
  meta?: {
    categories?: ContentCategory[]
    topics?: ContentTopic[]
    filters?: Partial<ContentFilterOptions>
  }
}

interface ContentDetailResponse {
  data: ApiContentItem
}

interface UiMemberProfile {
  id?: number
  name: string
  username: string
  slug: string
  initial: string
  color: string
  role: string
  group: string
  badge?: string | null
  bio: string
  location: string
  websiteUrl?: string | null
  isOnline: boolean
  isVerified: boolean
  isModerator: boolean
  joined: string
  lastActive: string
  interests: string[]
  stats: Record<string, any>
  badges: string[]
  href: string
  activities: UiMemberActivity[]
}

interface UiMemberActivity {
  icon: string
  tone: string
  title: string
  subtitle?: string | null
  category?: string | null
  time: string
}

interface ApiMemberProfile {
  id?: number
  display_name: string
  username: string
  slug: string
  initial: string
  color?: string | null
  role_label?: string | null
  group_label?: string | null
  badge_label?: string | null
  bio?: string | null
  location?: string | null
  website_url?: string | null
  is_online?: boolean
  is_verified?: boolean
  is_moderator?: boolean
  joined_label?: string | null
  last_active_label?: string | null
  interests?: string[]
  stats?: Record<string, any>
  badges?: string[]
  href?: string
  activities?: ApiMemberActivity[]
}

interface ApiMemberActivity {
  icon?: string | null
  tone?: string | null
  title: string
  subtitle?: string | null
  category?: string | null
  time_ago?: string | null
}

interface MemberIndexResponse {
  data: ApiMemberProfile[]
  meta?: {
    stats?: { total?: number; online?: number }
  }
}

interface MemberDetailResponse {
  data: ApiMemberProfile
}

interface UiMessageThread {
  id: number
  participant: UiMemberProfile
  preview: string
  time: string
  unread: number
  messages: UiMessage[]
}

interface UiMessage {
  id?: number
  side: 'in' | 'out'
  text: string
  time: string
  attachmentName?: string | null
  attachmentMeta?: Record<string, any>
  attachmentLabel: string
  avatarInitial: string
  avatarColor: string
}

interface ApiMessageThread {
  id: number
  participant: ApiMemberProfile
  preview?: string | null
  unread_count?: number
  last_message_label?: string | null
  messages?: ApiMessage[]
}

interface ApiMessage {
  id?: number
  side: 'in' | 'out'
  body: string
  attachment_name?: string | null
  attachment_meta?: Record<string, any> | null
  sender?: ApiMemberProfile | null
  sent_label?: string | null
  time_ago?: string | null
}

interface MessageIndexResponse {
  data: ApiMessageThread[]
}

interface MessageDetailResponse {
  data: ApiMessageThread
}

interface UiNotification {
  id?: number
  slug: string
  title: string
  text: string
  body: string
  bodyParagraphs: string[]
  category: string
  categorySlug: string
  time: string
  icon: string
  tone: string
  unread: boolean
  href: string
  detailHref: string
  author: string
  views: string
}

interface ApiNotification {
  id?: number
  title: string
  slug: string
  category: string
  category_slug: string
  icon: string
  tone: string
  excerpt?: string | null
  body?: string | null
  source_url?: string | null
  views?: number
  published_label?: string | null
  time_ago?: string | null
  read_at?: string | null
  unread?: boolean
  detail_href?: string
  href?: string
  author?: { name?: string | null; initial?: string | null } | null
}

interface NotificationStats {
  total: number
  unread: number
  announcements: number
  lab_results: number
  discussions: number
  vendors: number
}

interface NotificationCategory {
  name: string
  slug: string
  icon: string
  count: number
  unread: number
  latest?: string | null
}

interface NotificationIndexResponse {
  data: ApiNotification[]
  meta?: {
    stats?: NotificationStats
    categories?: NotificationCategory[]
  }
}

interface NotificationDetailResponse {
  data: ApiNotification
}

interface UserSettingsPayload {
  email_notifications: boolean
  push_notifications: boolean
  sound_enabled: boolean
  show_online: boolean
  public_profile: boolean
  profile_visibility: 'everyone' | 'members_only' | 'nobody'
  direct_messages: 'everyone' | 'members_only' | 'nobody'
  show_read_topics: boolean
  show_typing: boolean
  show_recent_activity: boolean
  personalize_experience: boolean
  allow_analytics: boolean
  compact_discussions: boolean
  show_online_members: boolean
  remember_content_filters: boolean
  theme: 'light' | 'dark' | 'system'
  language: string
}

type BooleanUserSettingKey = {
  [K in keyof UserSettingsPayload]: UserSettingsPayload[K] extends boolean ? K : never
}[keyof UserSettingsPayload]

interface UserSessionSummary {
  id: string
  ipAddress?: string | null
  userAgent?: string | null
  lastActivity?: string | null
  isCurrent?: boolean
  name?: string
  createdAt?: string | null
  expiresAt?: string | null
}

interface ApiTokenSummary {
  id: number
  name: string
  abilities?: string[]
  last_used_at?: string | null
  expires_at?: string | null
  created_at?: string | null
}

interface SessionsResponse {
  sessions?: UserSessionSummary[]
  tokens?: UserSessionSummary[]
}

interface ApiTokenIndexResponse {
  data: ApiTokenSummary[]
}

const avatarColors = ['purple', 'blue', 'green', 'pink', 'orange', 'teal']

const apiDiscussions = ref<UiDiscussion[]>([])
const apiDetailDiscussion = ref<UiDiscussion | null>(null)
const apiReplies = ref<UiReply[]>([])
const apiCategories = ref<ApiCategory[]>([])
const discussionsLoaded = ref(false)
const discussionSearch = ref('')
const activeCategory = ref('')
const discussionStatusMessage = ref('')
const showNewDiscussion = ref(false)
const creatingDiscussion = ref(false)
const discussionFormError = ref('')
const replyBody = ref('')
const submittingReply = ref(false)
const replyStatusMessage = ref('')
const discussionSort = ref<'latest' | 'replies' | 'views'>('latest')
const actionStatusMessage = ref('')
const followedDiscussionSlugs = ref<string[]>([])
const savedDiscussionSlugs = ref<string[]>([])
const votedReplyKeys = ref<string[]>([])
const apiLabResults = ref<UiLabResult[]>([])
const apiDetailLabResult = ref<UiLabResult | null>(null)
const labResultsLoaded = ref(false)
const labStats = ref<LabStats | null>(null)
const labSort = ref<'latest' | 'purity' | 'compound'>('latest')
const labDetailTab = ref<'overview' | 'certificate' | 'raw' | 'batch'>('overview')
const labSearch = ref('')
const labTypeFilter = ref('')
const labCompoundFilter = ref('')
const labVendorFilter = ref('')
const labLabFilter = ref('')
const labFilterOptions = ref<LabFilterOptions>({
  compound_types: [],
  compounds: [],
  vendors: [],
  labs: [],
})
const labStatusMessage = ref('')
const showSubmitLabResult = ref(false)
const submittingLabResult = ref(false)
const labFormError = ref('')
const apiVendors = ref<UiVendor[]>([])
const apiTopVendors = ref<UiVendor[]>([])
const apiDetailVendor = ref<UiVendor | null>(null)
const apiVendorReviews = ref<UiVendorReview[]>([])
const vendorsLoaded = ref(false)
const vendorSearch = ref('')
const vendorStatusFilter = ref('')
const vendorRatingFilter = ref('')
const vendorTagFilter = ref('')
const vendorFilterOptions = ref<VendorFilterOptions>({
  statuses: [],
  ratings: [],
  tags: [],
})
const vendorStatusMessage = ref('')
const vendorReviewStatusMessage = ref('')
const vendorReviewFormError = ref('')
const submittingVendorReview = ref(false)
const markingReviewHelpful = ref<number | undefined>()
const vendorSort = ref<'rating' | 'reviews' | 'name'>('rating')
const vendorReviewRatingFilter = ref('')
const vendorReviewProductFilter = ref('')
const vendorReviewTimeFilter = ref('all')
const vendorReviewSort = ref<'helpful' | 'recent'>('helpful')
const vendorDetailTab = ref<'overview' | 'reviews' | 'about'>('reviews')
const vendorStats = ref<VendorStats>({
  vendors_reviewed: 0,
  total_reviews: 0,
  average_rating: 0,
  would_buy_again: 0,
})
const apiAnnouncements = ref<UiAnnouncement[]>([])
const apiDetailAnnouncement = ref<UiAnnouncement | null>(null)
const announcementsLoaded = ref(false)
const announcementFilter = ref('all')
const announcementStatusMessage = ref('')
const announcementCategories = ref<AnnouncementCategory[]>([])
const announcementStats = ref<AnnouncementStats>({
  total: 0,
  pinned: 0,
  this_month: 0,
  total_views: 0,
  total_comments: 0,
})
const apiResearchArticles = ref<UiContentItem[]>([])
const apiDetailResearchArticle = ref<UiContentItem | null>(null)
const apiGuides = ref<UiContentItem[]>([])
const apiDetailGuide = ref<UiContentItem | null>(null)
const apiFaqs = ref<UiContentItem[]>([])
const contentCategories = ref<Record<string, ContentCategory[]>>({
  research: [],
  guide: [],
  faq: [],
})
const contentTopics = ref<Record<string, ContentTopic[]>>({
  research: [],
  guide: [],
  faq: [],
})
const emptyContentFilterOptions = (): ContentFilterOptions => ({
  categories: [],
  tags: [],
  compounds: [],
  sorts: [{ value: 'latest', label: 'Latest Added' }],
  date_bounds: {},
})
const contentFilterOptions = ref<Record<string, ContentFilterOptions>>({
  research: emptyContentFilterOptions(),
  guide: emptyContentFilterOptions(),
  faq: emptyContentFilterOptions(),
})
const contentLoaded = ref(false)
const contentStatusMessage = ref('')
const activeResearchCategory = ref('')
const activeGuideCategory = ref('')
const researchSearch = ref('')
const researchTagFilter = ref('')
const researchCompoundFilter = ref('')
const researchSort = ref('latest')
const researchDetailTab = ref<'article' | 'data' | 'references' | 'comments'>('article')
const researchPublishedFrom = ref('')
const researchPublishedTo = ref('')
const guideSearch = ref('')
const bookmarkedContentSlugs = ref<string[]>([])
const apiMembers = ref<UiMemberProfile[]>([])
const apiDetailMember = ref<UiMemberProfile | null>(null)
const memberStats = ref({ total: 0, online: 0 })
const communityStats = ref({ total_discussions: 0, total_replies: 0 })
const membersLoaded = ref(false)
const memberSearch = ref('')
const apiMessageThreads = ref<UiMessageThread[]>([])
const apiCurrentMessageThread = ref<UiMessageThread | null>(null)
const messagesLoaded = ref(false)
const messagesStatusMessage = ref('')
const messageSearch = ref('')
const messageBody = ref('')
const sendingMessage = ref(false)
const showMessageSafetyNotice = ref(true)
const apiNotifications = ref<UiNotification[]>([])
const apiDetailNotification = ref<UiNotification | null>(null)
const notificationsLoaded = ref(false)
const notificationStatusMessage = ref('')
const activeNotificationFilter = ref('all')
const markingNotificationsRead = ref(false)
const notificationStats = ref<NotificationStats>({
  total: 0,
  unread: 0,
  announcements: 0,
  lab_results: 0,
  discussions: 0,
  vendors: 0,
})
const notificationCategories = ref<NotificationCategory[]>([])
const accountForm = ref({
  username: '',
  name: '',
  email: '',
  bio: '',
  timezone: '',
  locale: 'en',
  website_url: '',
})
const passwordSettingsForm = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
})
const userSettings = ref<UserSettingsPayload>(defaultUserSettings())
const settingsLoaded = ref(false)
const savingSettings = ref(false)
const changingSettingsPassword = ref(false)
const settingsStatusMessage = ref('')
const userSessions = ref<UserSessionSummary[]>([])
const userApiTokens = ref<ApiTokenSummary[]>([])
const apiTokenForm = ref({
  name: '',
})
const newPlainApiToken = ref('')
const avatarFileInput = ref<HTMLInputElement | null>(null)
const uploadingAvatar = ref(false)
const newDiscussion = ref({
  title: '',
  body: '',
  category_slug: '',
})
const newLabResult = ref({
  compound_name: '',
  compound_type: '',
  use_case: '',
  vendor_name: '',
  batch_code: '',
  lab_name: '',
  tested_at: '',
  purity_percent: undefined as number | undefined,
  coa_filename: '',
  notes: '',
})
const newVendorReview = ref({
  rating: 5,
  title: '',
  body: '',
  product_name: '',
  tags: '',
  would_buy_again: true,
})

const discussions = computed(() => {
  const items = [...apiDiscussions.value]

  if (discussionSort.value === 'replies') {
    return items.sort((a, b) => b.replies - a.replies)
  }

  if (discussionSort.value === 'views') {
    return items.sort((a, b) => parseCount(b.views) - parseCount(a.views))
  }

  return items
})
const replies = computed(() => apiReplies.value)
const discussionCategories = computed(() => apiCategories.value)
const vendors = computed(() => {
  const items = [...apiVendors.value]

  if (vendorSort.value === 'reviews') {
    return items.sort((a, b) => b.reviews - a.reviews)
  }

  if (vendorSort.value === 'name') {
    return items.sort((a, b) => a.name.localeCompare(b.name))
  }

  return items.sort((a, b) => Number.parseFloat(b.rating || '0') - Number.parseFloat(a.rating || '0'))
})
const reviews = computed(() => {
  let items = [...apiVendorReviews.value]

  if (vendorReviewRatingFilter.value) {
    items = items.filter(review => String(review.rating) === vendorReviewRatingFilter.value)
  }

  if (vendorReviewProductFilter.value) {
    items = items.filter(review => review.productName === vendorReviewProductFilter.value)
  }

  if (vendorReviewTimeFilter.value === 'recent') {
    items.sort((a, b) => (Date.parse(b.date) || 0) - (Date.parse(a.date) || 0))
  }

  if (vendorReviewSort.value === 'helpful') {
    items.sort((a, b) => b.helpful - a.helpful)
  }

  return items
})
const announcements = computed(() => apiAnnouncements.value)
const announcementPreview = computed(() => announcements.value.slice(0, 3))
const articles = computed(() => apiResearchArticles.value)
const guides = computed(() => apiGuides.value)
const faqs = computed(() => apiFaqs.value)
const popularTopics = computed(() => contentTopics.value.research.length > 0 ? contentTopics.value.research : contentTopics.value.guide)
const researchSortLabel = computed(() => contentFilterOptions.value.research.sorts.find(sort => sort.value === researchSort.value)?.label ?? 'Latest Added')
const onlineMembers = computed(() => apiMembers.value.filter(member => member.isOnline).slice(0, 6))
const chats = computed(() => {
  const search = messageSearch.value.trim().toLowerCase()
  if (!search) {
    return apiMessageThreads.value
  }

  return apiMessageThreads.value.filter(thread => [
    thread.participant.name,
    thread.participant.username,
    thread.preview,
  ].some(value => value.toLowerCase().includes(search)))
})
const currentThread = computed(() => apiCurrentMessageThread.value ?? apiMessageThreads.value[0] ?? null)
const categoryFilters = computed<ApiCategory[]>(() => [
  {
    name: 'All Discussions',
    slug: '',
    icon: 'discussions',
    count: discussionCategories.value.reduce((total, category) => total + Number(category.count || 0), 0),
  },
  ...discussionCategories.value,
])
const discussionSortLabel = computed(() => {
  if (discussionSort.value === 'replies') return 'Most Replies'
  if (discussionSort.value === 'views') return 'Most Viewed'
  return 'Latest Activity'
})
const isFollowingDiscussion = computed(() => Boolean(detailDiscussion.value?.slug && followedDiscussionSlugs.value.includes(detailDiscussion.value.slug)))
const isSavedDiscussion = computed(() => Boolean(detailDiscussion.value?.slug && savedDiscussionSlugs.value.includes(detailDiscussion.value.slug)))
const currentDiscussionSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailDiscussion = computed(() => {
  return apiDetailDiscussion.value
    ?? discussions.value.find(topic => topic.slug === currentDiscussionSlug.value || topic.href.endsWith(`/${currentDiscussionSlug.value}`))
    ?? null
})
const detailParagraphs = computed(() => {
  const body = detailDiscussion.value?.body ?? detailDiscussion.value?.excerpt ?? ''
  return body.split(/\n+/).map(paragraph => paragraph.trim()).filter(Boolean)
})
const detailCategoryLabel = computed(() => detailDiscussion.value?.category || detailDiscussion.value?.tag || 'Discussion')
const discussionParticipants = computed(() => {
  const participants = new Map<string, { name: string; initial: string; color: string }>()
  if (detailDiscussion.value) {
    participants.set(detailDiscussion.value.author, {
      name: detailDiscussion.value.author,
      initial: detailDiscussion.value.initial,
      color: detailDiscussion.value.color,
    })
  }
  replies.value.forEach((reply) => {
    participants.set(reply.author, {
      name: reply.author,
      initial: reply.initial,
      color: reply.color,
    })
  })

  return Array.from(participants.values())
})
const similarDiscussionTopics = computed(() => discussions.value
  .filter(topic => topic.slug !== currentDiscussionSlug.value && topic.href !== detailDiscussion.value?.href)
  .slice(0, 3))
const currentAnnouncementSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailAnnouncement = computed(() => {
  return apiDetailAnnouncement.value
    ?? announcements.value.find(announcement => announcement.slug === currentAnnouncementSlug.value || announcement.href.endsWith(`/${currentAnnouncementSlug.value}`))
    ?? null
})
const announcementParagraphs = computed(() => {
  const body = detailAnnouncement.value?.body ?? detailAnnouncement.value?.text ?? ''
  return body.split(/\n+/).map(paragraph => paragraph.trim()).filter(Boolean)
})
const currentContentSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailArticle = computed(() => {
  return apiDetailResearchArticle.value
    ?? articles.value.find(article => article.slug === currentContentSlug.value || article.href.endsWith(`/${currentContentSlug.value}`))
    ?? null
})
const detailGuide = computed(() => {
  return apiDetailGuide.value
    ?? guides.value.find(guide => guide.slug === currentContentSlug.value || guide.href.endsWith(`/${currentContentSlug.value}`))
    ?? null
})
const detailGuideSteps = computed(() => {
  const steps = detailGuide.value?.metadata?.steps
  return Array.isArray(steps) ? steps : []
})
const articleBodyBlocks = computed(() => parseContentBlocks(detailArticle.value?.body ?? ''))
const guideBodyBlocks = computed(() => parseContentBlocks(detailGuide.value?.body ?? ''))
const articleHeadings = computed(() => articleBodyBlocks.value.filter(block => block.kind === 'heading').map(block => block.text))
const guideHeadings = computed(() => guideBodyBlocks.value.filter(block => block.kind === 'heading').map(block => block.text))
const currentMemberSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailMember = computed(() => {
  return apiDetailMember.value
    ?? apiMembers.value.find(member => member.slug === currentMemberSlug.value || member.href.endsWith(`/${currentMemberSlug.value}`))
    ?? null
})

function defaultUserSettings(): UserSettingsPayload {
  return {
    email_notifications: true,
    push_notifications: true,
    sound_enabled: true,
    show_online: true,
    public_profile: true,
    profile_visibility: 'everyone',
    direct_messages: 'members_only',
    show_read_topics: true,
    show_typing: true,
    show_recent_activity: true,
    personalize_experience: true,
    allow_analytics: false,
    compact_discussions: false,
    show_online_members: true,
    remember_content_filters: true,
    theme: 'dark',
    language: 'en',
  }
}

function userRecord(): Record<string, any> {
  return (authStore.user as Record<string, any> | null) ?? {}
}

function hydrateSettingsFromUser(user: Record<string, any>): void {
  accountForm.value = {
    username: String(user.username ?? ''),
    name: String(user.name ?? user.username ?? ''),
    email: String(user.email ?? ''),
    bio: String(user.bio ?? ''),
    timezone: String(user.timezone ?? ''),
    locale: String(user.locale ?? 'en'),
    website_url: String(user.website_url ?? ''),
  }

  const defaults = defaultUserSettings()
  userSettings.value = {
    email_notifications: Boolean(user.email_notifications ?? defaults.email_notifications),
    push_notifications: Boolean(user.push_notifications ?? defaults.push_notifications),
    sound_enabled: Boolean(user.sound_enabled ?? defaults.sound_enabled),
    show_online: Boolean(user.show_online ?? defaults.show_online),
    public_profile: Boolean(user.public_profile ?? defaults.public_profile),
    profile_visibility: (user.profile_visibility ?? defaults.profile_visibility) as UserSettingsPayload['profile_visibility'],
    direct_messages: (user.direct_messages ?? defaults.direct_messages) as UserSettingsPayload['direct_messages'],
    show_read_topics: Boolean(user.show_read_topics ?? defaults.show_read_topics),
    show_typing: Boolean(user.show_typing ?? defaults.show_typing),
    show_recent_activity: Boolean(user.show_recent_activity ?? defaults.show_recent_activity),
    personalize_experience: Boolean(user.personalize_experience ?? defaults.personalize_experience),
    allow_analytics: Boolean(user.allow_analytics ?? defaults.allow_analytics),
    compact_discussions: Boolean(user.compact_discussions ?? defaults.compact_discussions),
    show_online_members: Boolean(user.show_online_members ?? defaults.show_online_members),
    remember_content_filters: Boolean(user.remember_content_filters ?? defaults.remember_content_filters),
    theme: (user.theme ?? defaults.theme) as UserSettingsPayload['theme'],
    language: String(user.language ?? defaults.language),
  }
}

async function loadUserSettings(): Promise<void> {
  if (!page.value.startsWith('settings')) {
    return
  }

  settingsStatusMessage.value = ''

  if (!authStore.isAuthenticated) {
    settingsLoaded.value = true
    userSessions.value = []
    userApiTokens.value = []
    return
  }

  try {
    const response = await api.get<Record<string, any>>('/api/v1/user', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    authStore.user = response.data as any
    hydrateSettingsFromUser(response.data)
    settingsLoaded.value = true
  } catch {
    settingsStatusMessage.value = 'Unable to load account settings.'
    settingsLoaded.value = true
  }

  await Promise.all([
    loadUserSessions(),
    loadUserApiTokens(),
  ])
}

async function loadUserSessions(): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  try {
    const response = await api.get<SessionsResponse>('/api/v1/user/sessions', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    userSessions.value = [
      ...(response.data.sessions ?? []),
      ...(response.data.tokens ?? []),
    ]
  } catch {
    userSessions.value = []
  }
}

async function loadUserApiTokens(): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  try {
    const response = await api.get<ApiTokenIndexResponse>('/api/v1/user/api-tokens', {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    userApiTokens.value = response.data.data
  } catch {
    userApiTokens.value = []
  }
}

async function saveUserSettings(payload: Partial<UserSettingsPayload> = userSettings.value): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to update settings.'
    return
  }

  savingSettings.value = true
  settingsStatusMessage.value = ''

  try {
    const response = await api.patch<{ user?: Record<string, any> }>('/api/v1/user', payload)
    if (response.data.user) {
      authStore.user = response.data.user as any
      hydrateSettingsFromUser(response.data.user)
    }
    settingsStatusMessage.value = 'Settings saved.'
  } catch {
    settingsStatusMessage.value = 'Unable to save settings.'
  } finally {
    savingSettings.value = false
  }
}

async function saveAccountProfile(): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to update your account.'
    return
  }

  savingSettings.value = true
  settingsStatusMessage.value = ''

  try {
    const response = await api.patch<{ user?: Record<string, any> }>('/api/v1/user/profile', {
      username: accountForm.value.username,
      name: accountForm.value.name,
      bio: accountForm.value.bio,
      timezone: accountForm.value.timezone,
      locale: accountForm.value.locale,
      website_url: accountForm.value.website_url,
    })
    if (response.data.user) {
      authStore.user = response.data.user as any
      hydrateSettingsFromUser(response.data.user)
    }
    settingsStatusMessage.value = 'Profile saved.'
  } catch {
    settingsStatusMessage.value = 'Unable to save profile.'
  } finally {
    savingSettings.value = false
  }
}

async function changeSettingsPassword(): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to change your password.'
    return
  }

  if (passwordSettingsForm.value.new_password !== passwordSettingsForm.value.new_password_confirmation) {
    settingsStatusMessage.value = 'Password confirmation does not match.'
    return
  }

  changingSettingsPassword.value = true
  settingsStatusMessage.value = ''

  try {
    await api.post('/api/v1/user/change-password', passwordSettingsForm.value)
    passwordSettingsForm.value = {
      current_password: '',
      new_password: '',
      new_password_confirmation: '',
    }
    settingsStatusMessage.value = 'Password updated.'
  } catch {
    settingsStatusMessage.value = 'Unable to update password.'
  } finally {
    changingSettingsPassword.value = false
  }
}

async function toggleUserSetting(key: BooleanUserSettingKey): Promise<void> {
  const next = { ...userSettings.value, [key]: !userSettings.value[key] }
  userSettings.value = next
  await saveUserSettings({ [key]: next[key] } as Partial<UserSettingsPayload>)
}

async function setUserSetting<K extends keyof UserSettingsPayload>(key: K, value: UserSettingsPayload[K]): Promise<void> {
  userSettings.value = { ...userSettings.value, [key]: value }
  await saveUserSettings({ [key]: value } as Partial<UserSettingsPayload>)
}

async function createApiToken(): Promise<void> {
  if (!authStore.isAuthenticated) {
    settingsStatusMessage.value = 'Please log in to create API tokens.'
    return
  }

  settingsStatusMessage.value = ''
  const name = apiTokenForm.value.name.trim()

  if (!name) {
    settingsStatusMessage.value = 'Enter a token name first.'
    return
  }

  try {
    const response = await api.post<{ plain_text_token: string }>('/api/v1/user/api-tokens', {
      name,
      abilities: ['*'],
    })
    newPlainApiToken.value = response.data.plain_text_token
    apiTokenForm.value.name = ''
    settingsStatusMessage.value = 'API token created.'
    await loadUserApiTokens()
    await loadUserSessions()
  } catch {
    settingsStatusMessage.value = 'Unable to create API token.'
  }
}

async function deleteApiToken(tokenId: number): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  try {
    await api.delete(`/api/v1/user/api-tokens/${tokenId}`)
    await loadUserApiTokens()
    await loadUserSessions()
    settingsStatusMessage.value = 'API token revoked.'
  } catch {
    settingsStatusMessage.value = 'Unable to revoke API token.'
  }
}

async function uploadProfileAvatar(event: Event): Promise<void> {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) {
    return
  }

  uploadingAvatar.value = true
  settingsStatusMessage.value = ''

  try {
    const formData = new FormData()
    formData.append('avatar', file)
    const response = await api.post<{ user?: Record<string, any> }>('/api/v1/user/avatar', formData, {
      headers: { 'Content-Type': 'multipart/form-data' },
      skipDeduplication: true,
    })
    if (response.data.user) {
      authStore.user = response.data.user as any
      hydrateSettingsFromUser(response.data.user)
    }
    settingsStatusMessage.value = 'Profile photo updated.'
  } catch {
    settingsStatusMessage.value = 'Unable to upload profile photo.'
  } finally {
    uploadingAvatar.value = false
    input.value = ''
  }
}

function parseContentBlocks(body: string): Array<{ kind: 'heading' | 'paragraph' | 'list'; text: string; items?: string[] }> {
  const blocks: Array<{ kind: 'heading' | 'paragraph' | 'list'; text: string; items?: string[] }> = []
  let listItems: string[] = []

  const flushList = () => {
    if (listItems.length > 0) {
      blocks.push({ kind: 'list', text: listItems.join('|'), items: listItems })
      listItems = []
    }
  }

  body.split(/\n+/).map(line => line.trim()).filter(Boolean).forEach((line) => {
    if (line.startsWith('- ')) {
      listItems.push(line.slice(2))
      return
    }

    flushList()

    if (line.startsWith('## ')) {
      blocks.push({ kind: 'heading', text: line.slice(3) })
      return
    }

    if (line.startsWith('### ')) {
      blocks.push({ kind: 'heading', text: line.slice(4) })
      return
    }

    blocks.push({ kind: 'paragraph', text: line })
  })

  flushList()

  return blocks
}

function formatCount(value: number | undefined): string {
  const count = Number(value ?? 0)

  if (count >= 1000) {
    return `${(count / 1000).toFixed(count >= 10000 ? 0 : 1).replace(/\.0$/, '')}K`
  }

  return String(count)
}

function parseCount(value: string | number | undefined): number {
  if (typeof value === 'number') {
    return value
  }

  const raw = String(value ?? '0').trim().toLowerCase()
  const multiplier = raw.endsWith('k') ? 1000 : 1
  const numeric = Number.parseFloat(raw.replace(/[^0-9.]/g, ''))

  return Number.isFinite(numeric) ? numeric * multiplier : 0
}

function readLocalList(key: string): string[] {
  try {
    return JSON.parse(localStorage.getItem(key) || '[]')
  } catch {
    return []
  }
}

function writeLocalList(key: string, values: string[]): void {
  localStorage.setItem(key, JSON.stringify(values))
}

function toggleLocalValue(list: typeof followedDiscussionSlugs, key: string, value: string, addedMessage: string, removedMessage: string): void {
  const exists = list.value.includes(value)
  list.value = exists ? list.value.filter(item => item !== value) : [...list.value, value]
  writeLocalList(key, list.value)
  actionStatusMessage.value = exists ? removedMessage : addedMessage
}

function loadLocalActionState(): void {
  followedDiscussionSlugs.value = readLocalList('pv.followedDiscussions')
  savedDiscussionSlugs.value = readLocalList('pv.savedDiscussions')
  votedReplyKeys.value = readLocalList('pv.votedReplies')
  bookmarkedContentSlugs.value = readLocalList('pv.bookmarkedContent')
}

function syncFiltersFromRouteQuery(): void {
  const query = typeof route.query.q === 'string' ? route.query.q : ''

  if (page.value === 'discussions') discussionSearch.value = query
  if (page.value === 'labResults') labSearch.value = query
  if (page.value === 'vendorReviews') vendorSearch.value = query
  if (page.value === 'researchLibrary') researchSearch.value = query
  if (page.value === 'guides') guideSearch.value = query
  if (page.value === 'members') memberSearch.value = query
  if (page.value === 'messages') messageSearch.value = query
}

function cycleDiscussionSort(): void {
  discussionSort.value = discussionSort.value === 'latest' ? 'replies' : discussionSort.value === 'replies' ? 'views' : 'latest'
}

function cycleLabSort(): void {
  labSort.value = labSort.value === 'latest' ? 'purity' : labSort.value === 'purity' ? 'compound' : 'latest'
}

function cycleVendorSort(): void {
  vendorSort.value = vendorSort.value === 'rating' ? 'reviews' : vendorSort.value === 'reviews' ? 'name' : 'rating'
}

function toggleVendorReviewSort(): void {
  vendorReviewSort.value = vendorReviewSort.value === 'helpful' ? 'recent' : 'helpful'
}

function cycleResearchSort(): void {
  const options = contentFilterOptions.value.research.sorts.length > 0
    ? contentFilterOptions.value.research.sorts
    : [{ value: 'latest', label: 'Latest Added' }]
  const currentIndex = Math.max(0, options.findIndex(sort => sort.value === researchSort.value))
  researchSort.value = options[(currentIndex + 1) % options.length]?.value ?? 'latest'
  void loadResearchContent()
}

function toggleDiscussionFollow(): void {
  if (!detailDiscussion.value?.slug) return
  toggleLocalValue(
    followedDiscussionSlugs,
    'pv.followedDiscussions',
    detailDiscussion.value.slug,
    'You are following this discussion.',
    'You are no longer following this discussion.',
  )
}

function toggleDiscussionSave(): void {
  if (!detailDiscussion.value?.slug) return
  toggleLocalValue(
    savedDiscussionSlugs,
    'pv.savedDiscussions',
    detailDiscussion.value.slug,
    'Discussion saved.',
    'Discussion removed from saved items.',
  )
}

function isBookmarkedContent(slug: string): boolean {
  return bookmarkedContentSlugs.value.includes(slug)
}

function toggleContentBookmark(slug: string): void {
  toggleLocalValue(
    bookmarkedContentSlugs,
    'pv.bookmarkedContent',
    slug,
    'Content bookmarked.',
    'Bookmark removed.',
  )
}

async function shareCurrentPage(title = document.title): Promise<void> {
  const url = window.location.href

  try {
    if (navigator.share) {
      await navigator.share({ title, url })
    } else {
      await navigator.clipboard.writeText(url)
    }

    actionStatusMessage.value = 'Link copied.'
    labStatusMessage.value = 'Link copied.'
    contentStatusMessage.value = 'Link copied.'
  } catch {
    actionStatusMessage.value = 'Unable to share this page.'
  }
}

function printCurrentPage(): void {
  window.print()
}

function downloadLabReport(): void {
  if (!detailLabResult.value) return

  const result = detailLabResult.value
  const report = [
    result.name,
    `Vendor: ${result.vendor}`,
    `Batch: ${result.batch}`,
    `Lab: ${result.lab}`,
    `Tested: ${result.date}`,
    `Report ID: ${result.reportId}`,
    `Identity: ${result.identityResult}`,
    `Purity: ${result.purity}`,
    `Water Content: ${result.waterContent}`,
    `Peptide Content: ${result.peptideContent}`,
    '',
    result.notes,
  ].join('\n')

  const blob = new Blob([report], { type: 'text/plain;charset=utf-8' })
  const url = URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = `${result.slug || 'lab-report'}.txt`
  link.click()
  URL.revokeObjectURL(url)
  labStatusMessage.value = 'Report downloaded.'
}

async function findCurrentBatchResults(): Promise<void> {
  if (!detailLabResult.value) return

  labSearch.value = detailLabResult.value.batch
  labVendorFilter.value = ''
  await router.push('/lab-results')
  await loadLabResults()
}

async function findCurrentVendorLabResults(): Promise<void> {
  if (!detailLabResult.value) return

  labSearch.value = ''
  labVendorFilter.value = detailLabResult.value.vendor
  await router.push('/lab-results')
  await loadLabResults()
}

function jumpToReplyComposer(): void {
  document.getElementById('reply-composer')?.scrollIntoView({ behavior: 'smooth', block: 'center' })
}

function replyKey(reply: UiReply): string {
  return `${currentDiscussionSlug.value}:${reply.author}:${reply.time}:${reply.text.slice(0, 32)}`
}

function hasVotedReply(reply: UiReply): boolean {
  return votedReplyKeys.value.includes(replyKey(reply))
}

function replyVoteCount(reply: UiReply): number {
  return reply.votes + (hasVotedReply(reply) ? 1 : 0)
}

function toggleReplyVote(reply: UiReply): void {
  const key = replyKey(reply)
  votedReplyKeys.value = votedReplyKeys.value.includes(key)
    ? votedReplyKeys.value.filter(item => item !== key)
    : [...votedReplyKeys.value, key]
  writeLocalList('pv.votedReplies', votedReplyKeys.value)
}

function prepareReply(reply: UiReply, quote = false): void {
  replyBody.value = quote ? `> ${reply.text}\n\n` : `@${reply.author} `
  jumpToReplyComposer()
}

function jumpToVendorReviews(): void {
  document.querySelector('.pv-review-summary')?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function formatMetadataKey(key: string): string {
  return key.replace(/_/g, ' ').replace(/\b\w/g, character => character.toUpperCase())
}

function formatMetadataValue(value: unknown): string {
  if (Array.isArray(value)) {
    return value.join(', ')
  }

  if (value && typeof value === 'object') {
    return JSON.stringify(value)
  }

  return String(value ?? '')
}

function colorForName(value: string): string {
  const seed = value.charCodeAt(0) || 0
  return avatarColors[seed % avatarColors.length] ?? 'purple'
}

function mapDiscussion(item: ApiDiscussion): UiDiscussion {
  const author = item.author?.name ?? ''
  const initial = item.author?.initial ?? author.charAt(0).toUpperCase()
  const category = item.category?.name ?? undefined
  const color = item.category?.color && avatarColors.includes(item.category.color)
    ? item.category.color
    : colorForName(author)

  return {
    title: item.title,
    excerpt: item.excerpt ?? item.body?.slice(0, 140) ?? '',
    author,
    time: item.time_ago ?? '',
    replies: item.replies ?? 0,
    views: formatCount(item.views),
    initial,
    color,
    tag: item.tag ?? 'Discussion',
    href: item.href ?? `/discussions/${item.slug}`,
    slug: item.slug,
    body: item.body ?? undefined,
    category,
    lastActivity: item.last_activity ?? undefined,
  }
}

function mapReply(item: ApiReply): UiReply {
  const author = item.author?.name ?? ''

  return {
    author,
    initial: item.author?.initial ?? author.charAt(0).toUpperCase(),
    color: colorForName(author),
    badge: item.author?.badge ?? undefined,
    time: item.time_ago ?? '',
    text: item.body ?? '',
    file: item.attachment_name ?? undefined,
    votes: item.votes ?? 0,
  }
}

async function loadDiscussions(): Promise<void> {
  try {
    const response = await api.get<DiscussionIndexResponse>('/api/v1/community/discussions', {
      cacheTTL: 0,
      params: {
        search: discussionSearch.value || undefined,
        category: activeCategory.value || undefined,
      },
    })
    apiDiscussions.value = response.data.data.map(mapDiscussion)
    apiCategories.value = response.data.meta?.categories ?? apiCategories.value
    communityStats.value = response.data.meta?.stats ?? { total_discussions: 0, total_replies: 0 }
    discussionsLoaded.value = true
  } catch {
    apiDiscussions.value = []
    apiCategories.value = []
    communityStats.value = { total_discussions: 0, total_replies: 0 }
    discussionsLoaded.value = true
    discussionStatusMessage.value = 'Unable to load discussions from the API.'
  }
}

async function loadDiscussionDetail(): Promise<void> {
  if (page.value !== 'discussionDetail') {
    return
  }

  try {
    const response = await api.get<DiscussionDetailResponse>(`/api/v1/community/discussions/${currentDiscussionSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailDiscussion.value = mapDiscussion(response.data.data)
    apiReplies.value = (response.data.data.reply_items ?? []).map(mapReply)
  } catch {
    apiDetailDiscussion.value = null
    apiReplies.value = []
  }
}

async function syncCommunityContent(): Promise<void> {
  if (page.value.startsWith('legal')) {
    return
  }

  syncFiltersFromRouteQuery()

  await Promise.all([
    loadDiscussions(),
    loadLabResults(),
    loadVendors(),
    loadAnnouncements(),
    loadResearchContent(),
    loadGuidesContent(),
    loadFaqContent(),
    loadMembers(),
    loadMessages(),
    loadNotifications(),
    loadUserSettings(),
  ])
  await Promise.all([
    loadDiscussionDetail(),
    loadLabResultDetail(),
    loadVendorDetail(),
    loadAnnouncementDetail(),
    loadContentDetails(),
    loadMemberDetail(),
    loadNotificationDetail(),
  ])
}

function ensureAuthenticated(action: string): boolean {
  if (authStore.isAuthenticated) {
    return true
  }

  const message = `Please log in to ${action}.`
  discussionFormError.value = message
  replyStatusMessage.value = message
  discussionStatusMessage.value = message
  return false
}

function openNewDiscussion(): void {
  discussionFormError.value = ''
  if (!newDiscussion.value.category_slug) {
    newDiscussion.value.category_slug = discussionCategories.value[0]?.slug ?? ''
  }
  showNewDiscussion.value = true
}

function closeNewDiscussion(): void {
  showNewDiscussion.value = false
  discussionFormError.value = ''
}

async function submitNewDiscussion(): Promise<void> {
  if (!ensureAuthenticated('post a discussion')) {
    return
  }

  creatingDiscussion.value = true
  discussionFormError.value = ''

  try {
    const response = await api.post<DiscussionDetailResponse>('/api/v1/community/discussions', {
      title: newDiscussion.value.title,
      body: newDiscussion.value.body,
      category_slug: newDiscussion.value.category_slug || undefined,
    })
    const created = mapDiscussion(response.data.data)
    apiDiscussions.value = [created, ...apiDiscussions.value.filter(topic => topic.slug !== created.slug)]
    newDiscussion.value = { title: '', body: '', category_slug: discussionCategories.value[0]?.slug ?? '' }
    showNewDiscussion.value = false
    discussionStatusMessage.value = 'Discussion posted.'
    await router.push(created.href)
  } catch (error) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = apiError.response?.data?.errors
    discussionFormError.value = errors ? Object.values(errors)[0]?.[0] ?? 'Unable to post discussion.' : apiError.response?.data?.message ?? 'Unable to post discussion.'
  } finally {
    creatingDiscussion.value = false
  }
}

async function submitReply(): Promise<void> {
  if (!ensureAuthenticated('reply')) {
    return
  }

  const body = replyBody.value.trim()
  if (!body) {
    replyStatusMessage.value = 'Write a reply first.'
    return
  }

  submittingReply.value = true
  replyStatusMessage.value = ''

  try {
    const response = await api.post<{ data: ApiReply }>(`/api/v1/community/discussions/${currentDiscussionSlug.value}/replies`, {
      body,
    })
    apiReplies.value = [...apiReplies.value, mapReply(response.data.data)]
    if (detailDiscussion.value) {
      apiDetailDiscussion.value = {
        ...detailDiscussion.value,
        replies: detailDiscussion.value.replies + 1,
        lastActivity: 'just now',
      }
    }
    replyBody.value = ''
    replyStatusMessage.value = 'Reply posted.'
    await loadDiscussions()
  } catch (error) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = apiError.response?.data?.errors
    replyStatusMessage.value = errors ? Object.values(errors)[0]?.[0] ?? 'Unable to post reply.' : apiError.response?.data?.message ?? 'Unable to post reply.'
  } finally {
    submittingReply.value = false
  }
}

function setDiscussionCategory(slug: string): void {
  activeCategory.value = slug
  void loadDiscussions()
}

function applyDiscussionFilters(): void {
  void loadDiscussions()
}

function clearDiscussionFilters(): void {
  discussionSearch.value = ''
  activeCategory.value = ''
  void loadDiscussions()
}

onMounted(() => {
  loadLocalActionState()
  void syncCommunityContent()
})

watch(() => route.fullPath, () => {
  void syncCommunityContent()
})

const labResults = computed(() => {
  const items = [...apiLabResults.value]

  if (labSort.value === 'purity') {
    return items.sort((a, b) => Number(b.purityPercent ?? 0) - Number(a.purityPercent ?? 0))
  }

  if (labSort.value === 'compound') {
    return items.sort((a, b) => a.name.localeCompare(b.name))
  }

  return items
})
const currentLabSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailLabResult = computed(() => {
  return apiDetailLabResult.value
    ?? labResults.value.find(result => result.slug === currentLabSlug.value || result.href.endsWith(`/${currentLabSlug.value}`))
    ?? null
})
const labMetricCards = computed(() => [
  { label: 'Total Results', value: formatCount(labStats.value?.total), icon: 'document' },
  { label: 'Batches Tested', value: formatCount(labStats.value?.batches), icon: 'box' },
  { label: 'Avg. Purity', value: labStats.value?.avg_purity !== undefined ? `${labStats.value.avg_purity}%` : '0%', icon: 'shield' },
  { label: 'Labs Used', value: formatCount(labStats.value?.labs), icon: 'library' },
])
const labSortLabel = computed(() => {
  if (labSort.value === 'purity') return 'Highest Purity'
  if (labSort.value === 'compound') return 'Compound A-Z'
  return 'Latest First'
})
const labHasActiveFilters = computed(() => Boolean(labSearch.value || labTypeFilter.value || labCompoundFilter.value || labVendorFilter.value || labLabFilter.value))
const detailLabRingStyle = computed(() => {
  const score = detailLabResult.value?.purityPercent ?? Number.parseFloat(detailLabResult.value?.purity ?? '0')
  return { '--score': `${Number.isFinite(score) ? score : 0}%` } as Record<string, string>
})

function formatPercent(value: number | null | undefined, fallback = ''): string {
  if (value === null || value === undefined) {
    return fallback
  }

  return `${Number(value).toFixed(2).replace(/\.?0+$/, '')}%`
}

function mapLabResult(item: ApiLabResult): UiLabResult {
  const name = item.compound_name

  return {
    id: item.id,
    name,
    slug: item.slug,
    type: item.compound_type ?? 'Unspecified',
    use: item.use_case ?? 'Unspecified',
    vendor: item.vendor_name,
    batch: item.batch_code,
    lab: item.lab_name,
    date: item.tested_date ?? item.tested_at ?? '',
    receivedDate: item.received_date ?? item.received_at ?? '',
    reportId: item.report_id ?? '',
    sampleType: item.sample_type ?? '',
    sampleCondition: item.sample_condition ?? '',
    purity: item.purity ?? formatPercent(item.purity_percent),
    purityPercent: item.purity_percent,
    waterContent: formatPercent(item.water_content_percent),
    peptideContent: formatPercent(item.peptide_content_percent, item.purity ?? ''),
    identityResult: item.identity_result ?? '',
    overallResult: item.overall_result ?? '',
    coaFilename: item.coa_filename,
    notes: item.notes ?? '',
    views: formatCount(item.views),
    comments: item.comments ?? 0,
    color: colorForName(name),
    href: item.href ?? `/lab-results/${item.slug}`,
    submittedBy: item.submitted_by?.name ?? undefined,
    status: item.status ?? undefined,
    isVerified: item.is_verified,
  }
}

async function loadLabResults(): Promise<void> {
  try {
    const response = await api.get<LabResultIndexResponse>('/api/v1/community/lab-results', {
      cacheTTL: 0,
      params: {
        search: labSearch.value || undefined,
        compound_type: labTypeFilter.value || undefined,
        compound: labCompoundFilter.value || undefined,
        vendor: labVendorFilter.value || undefined,
        lab: labLabFilter.value || undefined,
      },
    })
    apiLabResults.value = response.data.data.map(mapLabResult)
    labStats.value = response.data.meta?.stats ?? labStats.value
    labFilterOptions.value = {
      compound_types: response.data.meta?.filters?.compound_types ?? [],
      compounds: response.data.meta?.filters?.compounds ?? [],
      vendors: response.data.meta?.filters?.vendors ?? [],
      labs: response.data.meta?.filters?.labs ?? [],
    }
    labResultsLoaded.value = true
  } catch {
    apiLabResults.value = []
    labStats.value = { total: 0, batches: 0, avg_purity: 0, labs: 0 }
    labFilterOptions.value = { compound_types: [], compounds: [], vendors: [], labs: [] }
    labResultsLoaded.value = true
    labStatusMessage.value = 'Unable to load lab results from the API.'
  }
}

async function loadLabResultDetail(): Promise<void> {
  if (page.value !== 'labReport') {
    return
  }

  try {
    const response = await api.get<LabResultDetailResponse>(`/api/v1/community/lab-results/${currentLabSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailLabResult.value = mapLabResult(response.data.data)
  } catch {
    apiDetailLabResult.value = null
  }
}

function setLabTypeFilter(type: string): void {
  labTypeFilter.value = type
  void loadLabResults()
}

function applyLabFilters(): void {
  void loadLabResults()
}

function clearLabFilters(): void {
  labSearch.value = ''
  labTypeFilter.value = ''
  labCompoundFilter.value = ''
  labVendorFilter.value = ''
  labLabFilter.value = ''
  void loadLabResults()
}

function openSubmitLabResult(): void {
  labFormError.value = ''
  showSubmitLabResult.value = true
}

function closeSubmitLabResult(): void {
  showSubmitLabResult.value = false
  labFormError.value = ''
}

async function submitLabResult(): Promise<void> {
  if (!authStore.isAuthenticated) {
    labFormError.value = 'Please log in to submit a lab result.'
    labStatusMessage.value = labFormError.value
    return
  }

  submittingLabResult.value = true
  labFormError.value = ''

  try {
    const payload = Object.fromEntries(
      Object.entries(newLabResult.value).filter(([, value]) => value !== '' && value !== undefined && value !== null),
    )
    const response = await api.post<LabResultDetailResponse>('/api/v1/community/lab-results', payload)
    const created = mapLabResult(response.data.data)
    labStatusMessage.value = `${created.name} was submitted for admin review.`
    showSubmitLabResult.value = false
    newLabResult.value = {
      compound_name: '',
      compound_type: 'Peptide',
      use_case: '',
      vendor_name: '',
      batch_code: '',
      lab_name: '',
      tested_at: '',
      purity_percent: undefined,
      coa_filename: '',
      notes: '',
    }
  } catch (error) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = apiError.response?.data?.errors
    labFormError.value = errors ? Object.values(errors)[0]?.[0] ?? 'Unable to submit lab result.' : apiError.response?.data?.message ?? 'Unable to submit lab result.'
  } finally {
    submittingLabResult.value = false
  }
}

const currentVendorSlug = computed(() => {
  const parts = route.path.split('/').filter(Boolean)
  return String(route.params.slug ?? parts[parts.length - 1] ?? '')
})
const detailVendor = computed(() => {
  return apiDetailVendor.value
    ?? vendors.value.find(vendor => vendor.slug === currentVendorSlug.value || vendor.href.endsWith(`/${currentVendorSlug.value}`))
    ?? null
})
const vendorMetricCards = computed(() => [
  { label: 'Vendors Reviewed', value: formatCount(vendorStats.value.vendors_reviewed), icon: 'shield' },
  { label: 'Total Reviews', value: formatCount(vendorStats.value.total_reviews), icon: 'document' },
  { label: 'Average Rating', value: `${vendorStats.value.average_rating} / 5`, icon: 'star' },
  { label: 'Would Buy Again', value: `${vendorStats.value.would_buy_again}%`, icon: 'heart' },
])
const firstVendorReviewHref = computed(() => vendors.value[0] ? `${vendors.value[0].href}/review` : '')
const topVendors = computed(() => apiTopVendors.value.length > 0 ? apiTopVendors.value : vendors.value.slice(0, 5))
const vendorHasActiveFilters = computed(() => Boolean(vendorSearch.value || vendorStatusFilter.value || vendorRatingFilter.value || vendorTagFilter.value))
const vendorSortLabel = computed(() => {
  if (vendorSort.value === 'reviews') return 'Most Reviewed'
  if (vendorSort.value === 'name') return 'Name A-Z'
  return 'Highest Rated'
})
const vendorReviewProductOptions = computed(() => [...new Set(apiVendorReviews.value.map(review => review.productName).filter(Boolean) as string[])])
const vendorReviewSortLabel = computed(() => vendorReviewSort.value === 'helpful' ? 'Most Helpful' : 'Recent First')

function mapVendor(item: ApiVendor): UiVendor {
  return {
    id: item.id,
    name: item.name,
    slug: item.slug,
    logo: item.logo_initials ?? item.name.slice(0, 2).toUpperCase(),
    logoText: item.logo_text ?? item.name,
    class: item.logo_class ?? '',
    status: item.status_label ?? '',
    statusClass: item.status_class ?? '',
    rating: item.rating_label ?? (item.average_rating !== undefined ? String(item.average_rating) : ''),
    reviews: item.review_count ?? 0,
    since: item.member_since_label ?? '',
    buyAgain: item.would_buy_again_label ?? '',
    chips: item.tags ?? [],
    tone: item.tone ?? '',
    href: item.href ?? `/vendor-reviews/${item.slug}`,
    description: item.description ?? '',
    websiteUrl: item.website_url ?? null,
    lastActive: item.last_active_label ?? '',
    responseRate: item.response_rate_label ?? '',
    avgResponseTime: item.avg_response_time ?? '',
    topProducts: item.top_products ?? [],
    ratingDistribution: item.rating_distribution ?? [],
  }
}

function mapVendorReview(item: ApiVendorReview): UiVendorReview {
  const author = item.author?.name ?? ''

  return {
    id: item.id,
    author,
    initial: item.author?.initial ?? author.charAt(0).toUpperCase(),
    color: colorForName(author),
    date: item.reviewed_date ?? '',
    rating: item.rating,
    title: item.title,
    text: item.body,
    productName: item.product_name,
    chips: item.tags ?? [],
    helpful: item.helpful_count ?? 0,
  }
}

async function loadVendors(): Promise<void> {
  try {
    const response = await api.get<VendorIndexResponse>('/api/v1/community/vendors', {
      cacheTTL: 0,
      params: {
        search: vendorSearch.value || undefined,
        status: vendorStatusFilter.value || undefined,
        rating_min: vendorRatingFilter.value || undefined,
        tag: vendorTagFilter.value || undefined,
      },
    })
    apiVendors.value = response.data.data.map(mapVendor)
    apiTopVendors.value = (response.data.meta?.top_vendors ?? []).map(mapVendor)
    vendorStats.value = response.data.meta?.stats ?? vendorStats.value
    vendorFilterOptions.value = {
      statuses: response.data.meta?.filters?.statuses ?? [],
      ratings: response.data.meta?.filters?.ratings ?? [],
      tags: response.data.meta?.filters?.tags ?? [],
    }
    vendorsLoaded.value = true
  } catch {
    apiVendors.value = []
    apiTopVendors.value = []
    vendorsLoaded.value = true
    vendorStats.value = {
      vendors_reviewed: 0,
      total_reviews: 0,
      average_rating: 0,
      would_buy_again: 0,
    }
    vendorStatusMessage.value = 'Unable to load vendors from the API.'
  }
}

async function loadVendorDetail(): Promise<void> {
  if (page.value !== 'vendorDetail' && page.value !== 'reviewModal') {
    return
  }

  try {
    const response = await api.get<VendorDetailResponse>(`/api/v1/community/vendors/${currentVendorSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailVendor.value = mapVendor(response.data.data)
    apiVendorReviews.value = (response.data.data.review_items ?? []).map(mapVendorReview)
  } catch {
    apiDetailVendor.value = null
    apiVendorReviews.value = []
  }
}

function setVendorStatusFilter(status: string): void {
  vendorStatusFilter.value = status
  void loadVendors()
}

function applyVendorFilters(): void {
  void loadVendors()
}

function clearVendorFilters(): void {
  vendorSearch.value = ''
  vendorStatusFilter.value = ''
  vendorRatingFilter.value = ''
  vendorTagFilter.value = ''
  void loadVendors()
}

async function submitVendorReview(): Promise<void> {
  if (!authStore.isAuthenticated) {
    vendorReviewFormError.value = 'Please log in to submit a vendor review.'
    return
  }

  if (!detailVendor.value) {
    vendorReviewFormError.value = 'Vendor not found.'
    return
  }

  submittingVendorReview.value = true
  vendorReviewFormError.value = ''

  try {
    await api.post(`/api/v1/community/vendors/${detailVendor.value.slug}/reviews`, {
      rating: newVendorReview.value.rating,
      title: newVendorReview.value.title,
      body: newVendorReview.value.body,
      product_name: newVendorReview.value.product_name || undefined,
      tags: newVendorReview.value.tags.split(',').map(tag => tag.trim()).filter(Boolean),
      would_buy_again: newVendorReview.value.would_buy_again,
    })
    vendorReviewStatusMessage.value = 'Review submitted for admin approval.'
    newVendorReview.value = {
      rating: 5,
      title: '',
      body: '',
      product_name: '',
      tags: '',
      would_buy_again: true,
    }
    await router.push(detailVendor.value.href)
  } catch (error) {
    const apiError = error as { response?: { data?: { message?: string; errors?: Record<string, string[]> } } }
    const errors = apiError.response?.data?.errors
    vendorReviewFormError.value = errors ? Object.values(errors)[0]?.[0] ?? 'Unable to submit review.' : apiError.response?.data?.message ?? 'Unable to submit review.'
  } finally {
    submittingVendorReview.value = false
  }
}

async function markReviewHelpful(review: UiVendorReview): Promise<void> {
  if (!authStore.isAuthenticated) {
    vendorReviewStatusMessage.value = 'Please log in to mark reviews helpful.'
    return
  }

  if (!review.id) {
    return
  }

  markingReviewHelpful.value = review.id
  vendorReviewStatusMessage.value = ''

  try {
    const response = await api.post<{ data: ApiVendorReview }>(`/api/v1/community/vendor-reviews/${review.id}/helpful`)
    const updated = mapVendorReview(response.data.data)
    apiVendorReviews.value = apiVendorReviews.value.map(item => item.id === updated.id ? updated : item)
    vendorReviewStatusMessage.value = 'Thanks for the feedback.'
  } catch {
    vendorReviewStatusMessage.value = 'Unable to mark review helpful.'
  } finally {
    markingReviewHelpful.value = undefined
  }
}

function mapAnnouncement(item: ApiAnnouncement): UiAnnouncement {
  const author = item.author?.name ?? ''

  return {
    id: item.id,
    title: item.title,
    slug: item.slug,
    category: item.category,
    icon: item.icon ?? 'megaphone',
    tone: item.tone ?? 'purple',
    text: item.excerpt ?? item.body?.slice(0, 180) ?? '',
    body: item.body ?? item.excerpt ?? '',
    date: item.published_label ?? '',
    time: item.time_ago ?? '',
    comments: item.comments ?? 0,
    views: formatCount(item.views),
    pinned: item.is_pinned ?? false,
    href: item.href ?? `/announcements/${item.slug}`,
    author,
    authorInitial: item.author?.initial ?? author.charAt(0).toUpperCase(),
  }
}

async function loadAnnouncements(): Promise<void> {
  try {
    const response = await api.get<AnnouncementIndexResponse>('/api/v1/community/announcements', {
      cacheTTL: 0,
      params: {
        pinned: announcementFilter.value === 'pinned' ? true : undefined,
        category: announcementFilter.value !== 'all' && announcementFilter.value !== 'pinned' ? announcementFilter.value : undefined,
      },
    })
    apiAnnouncements.value = response.data.data.map(mapAnnouncement)
    announcementStats.value = response.data.meta?.stats ?? announcementStats.value
    announcementCategories.value = response.data.meta?.categories ?? announcementCategories.value
    announcementsLoaded.value = true
    announcementStatusMessage.value = ''
  } catch {
    apiAnnouncements.value = []
    announcementCategories.value = []
    announcementStats.value = {
      total: 0,
      pinned: 0,
      this_month: 0,
      total_views: 0,
      total_comments: 0,
    }
    announcementsLoaded.value = true
    announcementStatusMessage.value = 'Unable to load announcements from the API.'
  }
}

async function loadAnnouncementDetail(): Promise<void> {
  if (page.value !== 'announcementDetail') {
    return
  }

  try {
    const response = await api.get<AnnouncementDetailResponse>(`/api/v1/community/announcements/${currentAnnouncementSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailAnnouncement.value = mapAnnouncement(response.data.data)
  } catch {
    apiDetailAnnouncement.value = null
  }
}

function setAnnouncementFilter(filter: string): void {
  announcementFilter.value = filter
  void loadAnnouncements()
}

function mapContent(item: ApiContentItem): UiContentItem {
  const author = item.author?.name ?? ''

  return {
    id: item.id,
    type: item.type,
    title: item.title,
    slug: item.slug,
    tag: item.tag ?? item.category,
    category: item.category,
    excerpt: item.excerpt ?? '',
    body: item.body ?? '',
    date: item.published_label ?? '',
    time: item.time_ago ?? '',
    views: formatCount(item.views),
    downloads: formatCount(item.downloads),
    comments: item.comments ?? 0,
    readMinutes: item.read_minutes ?? 5,
    timeLabel: item.read_label ?? `${item.read_minutes ?? 5} min`,
    href: item.href ?? (item.type === 'guide' ? `/guides/${item.slug}` : `/research-library/${item.slug}`),
    imageIndex: item.image_index ?? 0,
    author,
    authorInitial: item.author?.initial ?? author.slice(0, 2).toUpperCase(),
    authorBadge: item.author?.badge ?? null,
    metadata: item.metadata ?? {},
  }
}

function mapMember(item: ApiMemberProfile): UiMemberProfile {
  return {
    id: item.id,
    name: item.display_name,
    username: item.username,
    slug: item.slug,
    initial: item.initial,
    color: item.color ?? colorForName(item.display_name),
    role: item.role_label ?? '',
    group: item.group_label ?? '',
    badge: item.badge_label ?? null,
    bio: item.bio ?? '',
    location: item.location ?? '',
    websiteUrl: item.website_url ?? null,
    isOnline: item.is_online ?? false,
    isVerified: item.is_verified ?? false,
    isModerator: item.is_moderator ?? false,
    joined: item.joined_label ?? '',
    lastActive: item.last_active_label ?? '',
    interests: item.interests ?? [],
    stats: item.stats ?? {},
    badges: item.badges ?? [],
    href: item.href ?? `/members/${item.slug}`,
    activities: (item.activities ?? []).map(activity => ({
      icon: activity.icon ?? 'message',
      tone: activity.tone ?? 'purple',
      title: activity.title,
      subtitle: activity.subtitle,
      category: activity.category,
      time: activity.time_ago ?? '',
    })),
  }
}

function mapMessageThread(item: ApiMessageThread): UiMessageThread {
  const participant = mapMember(item.participant)

  return {
    id: item.id,
    participant,
    preview: item.preview ?? '',
    time: item.last_message_label ?? '',
    unread: item.unread_count ?? 0,
    messages: (item.messages ?? []).map(message => mapMessage(message, participant)),
  }
}

function mapMessage(item: ApiMessage, participant?: UiMemberProfile): UiMessage {
  const sender = item.sender ? mapMember(item.sender) : (item.side === 'in' ? participant : null)
  const attachmentMeta = item.attachment_meta ?? {}
  const attachmentType = String(attachmentMeta.type ?? '')
  const attachmentSize = String(attachmentMeta.size ?? '')

  return {
    id: item.id,
    side: item.side,
    text: item.body,
    time: item.sent_label ?? item.time_ago ?? '',
    attachmentName: item.attachment_name,
    attachmentMeta,
    attachmentLabel: [attachmentType, attachmentSize].filter(Boolean).join(' · '),
    avatarInitial: sender?.initial ?? '',
    avatarColor: sender?.color ?? '',
  }
}

async function loadResearchContent(): Promise<void> {
  try {
    const response = await api.get<ContentIndexResponse>('/api/v1/community/research-library', {
      cacheTTL: 0,
      params: {
        search: researchSearch.value || undefined,
        category: activeResearchCategory.value || undefined,
        tag: researchTagFilter.value || undefined,
        compound: researchCompoundFilter.value || undefined,
        sort: researchSort.value || undefined,
        published_from: researchPublishedFrom.value || undefined,
        published_to: researchPublishedTo.value || undefined,
      },
    })
    apiResearchArticles.value = response.data.data.map(mapContent)
    contentCategories.value.research = response.data.meta?.categories ?? []
    contentTopics.value.research = response.data.meta?.topics ?? []
    contentFilterOptions.value.research = {
      categories: response.data.meta?.filters?.categories ?? response.data.meta?.categories ?? [],
      tags: response.data.meta?.filters?.tags ?? [],
      compounds: response.data.meta?.filters?.compounds ?? [],
      sorts: response.data.meta?.filters?.sorts ?? [{ value: 'latest', label: 'Latest Added' }],
      date_bounds: response.data.meta?.filters?.date_bounds ?? {},
    }
    contentLoaded.value = true
  } catch {
    apiResearchArticles.value = []
    contentFilterOptions.value.research = emptyContentFilterOptions()
    contentLoaded.value = true
    contentStatusMessage.value = 'Unable to load research content from the API.'
  }
}

function applyResearchFilters(): void {
  void loadResearchContent()
}

function clearResearchFilters(): void {
  researchSearch.value = ''
  activeResearchCategory.value = ''
  researchTagFilter.value = ''
  researchCompoundFilter.value = ''
  researchSort.value = 'latest'
  researchPublishedFrom.value = ''
  researchPublishedTo.value = ''
  void loadResearchContent()
}

async function loadGuidesContent(): Promise<void> {
  try {
    const response = await api.get<ContentIndexResponse>('/api/v1/community/guides', {
      cacheTTL: 0,
      params: {
        search: guideSearch.value || undefined,
        category: activeGuideCategory.value || undefined,
      },
    })
    apiGuides.value = response.data.data.map(mapContent)
    contentCategories.value.guide = response.data.meta?.categories ?? []
    contentTopics.value.guide = response.data.meta?.topics ?? []
    contentLoaded.value = true
  } catch {
    apiGuides.value = []
    contentLoaded.value = true
    contentStatusMessage.value = 'Unable to load guides from the API.'
  }
}

async function loadFaqContent(): Promise<void> {
  try {
    const response = await api.get<ContentIndexResponse>('/api/v1/community/faqs', {
      cacheTTL: 0,
    })
    apiFaqs.value = response.data.data.map(mapContent)
    contentCategories.value.faq = response.data.meta?.categories ?? []
    contentTopics.value.faq = response.data.meta?.topics ?? []
  } catch {
    apiFaqs.value = []
  }
}

async function loadContentDetails(): Promise<void> {
  if (page.value === 'researchArticle') {
    try {
      const response = await api.get<ContentDetailResponse>(`/api/v1/community/research-library/${currentContentSlug.value}`, {
        cacheTTL: 0,
        skipDeduplication: true,
      })
      apiDetailResearchArticle.value = mapContent(response.data.data)
    } catch {
      apiDetailResearchArticle.value = null
    }
  }

  if (page.value === 'guideDetail') {
    try {
      const response = await api.get<ContentDetailResponse>(`/api/v1/community/guides/${currentContentSlug.value}`, {
        cacheTTL: 0,
        skipDeduplication: true,
      })
      apiDetailGuide.value = mapContent(response.data.data)
    } catch {
      apiDetailGuide.value = null
    }
  }
}

async function loadMembers(): Promise<void> {
  try {
    const response = await api.get<MemberIndexResponse>('/api/v1/community/members', {
      cacheTTL: 0,
      params: {
        search: memberSearch.value || undefined,
      },
    })
    apiMembers.value = response.data.data.map(mapMember)
    memberStats.value = {
      total: response.data.meta?.stats?.total ?? 0,
      online: response.data.meta?.stats?.online ?? 0,
    }
    membersLoaded.value = true
  } catch {
    apiMembers.value = []
    memberStats.value = { total: 0, online: 0 }
    membersLoaded.value = true
  }
}

async function loadMemberDetail(): Promise<void> {
  if (page.value !== 'memberDetail' || !currentMemberSlug.value) {
    apiDetailMember.value = null
    return
  }

  try {
    const response = await api.get<MemberDetailResponse>(`/api/v1/community/members/${currentMemberSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailMember.value = mapMember(response.data.data)
  } catch {
    apiDetailMember.value = null
  }
}

async function loadMessages(): Promise<void> {
  try {
    const response = await api.get<MessageIndexResponse>('/api/v1/community/messages', {
      cacheTTL: 0,
    })
    apiMessageThreads.value = response.data.data.map(mapMessageThread)
    messagesLoaded.value = true

    const firstThread = apiMessageThreads.value[0]
    if (firstThread) {
      await loadMessageThread(firstThread.id)
    }
  } catch {
    apiMessageThreads.value = []
    apiCurrentMessageThread.value = null
    messagesLoaded.value = true
    messagesStatusMessage.value = 'Unable to load messages from the API.'
  }
}

async function loadMessageThread(threadId: number): Promise<void> {
  try {
    const response = await api.get<MessageDetailResponse>(`/api/v1/community/messages/${threadId}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiCurrentMessageThread.value = mapMessageThread(response.data.data)
  } catch {
    apiCurrentMessageThread.value = null
  }
}

async function startMessage(profile: UiMemberProfile): Promise<void> {
  if (!profile.id) {
    return
  }

  messagesStatusMessage.value = ''

  try {
    const response = await api.post<MessageDetailResponse>('/api/v1/community/messages', {
      participant_user_id: profile.id,
    })
    const thread = mapMessageThread(response.data.data)
    apiCurrentMessageThread.value = thread
    apiMessageThreads.value = [thread, ...apiMessageThreads.value.filter(item => item.id !== thread.id)]
    await router.push('/messages')
  } catch {
    messagesStatusMessage.value = 'Unable to start a message thread with this member.'
  }
}

async function sendMessage(): Promise<void> {
  const body = messageBody.value.trim()
  if (!body || !currentThread.value) {
    return
  }

  sendingMessage.value = true

  try {
    const response = await api.post<{ data: ApiMessage }>(`/api/v1/community/messages/${currentThread.value.id}/messages`, { body })
    const message = mapMessage(response.data.data)
    apiCurrentMessageThread.value = {
      ...currentThread.value,
      preview: message.text,
      time: message.time,
      messages: [...currentThread.value.messages, message],
    }
    messageBody.value = ''
  } finally {
    sendingMessage.value = false
  }
}

function mapNotification(item: ApiNotification): UiNotification {
  const body = item.body || item.excerpt || ''
  const author = item.author?.name ?? ''

  return {
    id: item.id,
    slug: item.slug,
    title: item.title,
    text: item.excerpt || body,
    body,
    bodyParagraphs: body.split(/\n+/).map(paragraph => paragraph.trim()).filter(Boolean),
    category: item.category,
    categorySlug: item.category_slug,
    time: item.time_ago ?? item.published_label ?? '',
    icon: item.icon,
    tone: item.tone,
    unread: Boolean(item.unread),
    href: item.href ?? item.source_url ?? `/notifications/${item.slug}`,
    detailHref: item.detail_href ?? `/notifications/${item.slug}`,
    author,
    views: formatCount(item.views),
  }
}

async function loadNotifications(): Promise<void> {
  try {
    const category = ['all', 'unread'].includes(activeNotificationFilter.value) ? undefined : activeNotificationFilter.value
    const status = activeNotificationFilter.value === 'unread' ? 'unread' : undefined
    const response = await api.get<NotificationIndexResponse>('/api/v1/community/notifications', {
      cacheTTL: 0,
      params: {
        category,
        status,
      },
    })
    apiNotifications.value = response.data.data.map(mapNotification)
    notificationStats.value = response.data.meta?.stats ?? notificationStats.value
    notificationCategories.value = response.data.meta?.categories ?? []
    notificationsLoaded.value = true
    notificationStatusMessage.value = ''
  } catch {
    apiNotifications.value = []
    notificationCategories.value = []
    notificationStats.value = {
      total: 0,
      unread: 0,
      announcements: 0,
      lab_results: 0,
      discussions: 0,
      vendors: 0,
    }
    notificationsLoaded.value = true
    notificationStatusMessage.value = 'Unable to load notifications from the API.'
  }
}

async function loadNotificationDetail(): Promise<void> {
  if (page.value !== 'notificationDetail') {
    return
  }

  try {
    const response = await api.get<NotificationDetailResponse>(`/api/v1/community/notifications/${currentNotificationSlug.value}`, {
      cacheTTL: 0,
      skipDeduplication: true,
    })
    apiDetailNotification.value = mapNotification(response.data.data)
  } catch {
    apiDetailNotification.value = null
  }
}

function setNotificationFilter(filter: string): void {
  activeNotificationFilter.value = filter
  void loadNotifications()
}

async function markNotificationRead(slug: string): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  markingNotificationsRead.value = true

  try {
    const response = await api.post<NotificationDetailResponse>(`/api/v1/community/notifications/${slug}/read`)
    const updated = mapNotification(response.data.data)
    apiNotifications.value = apiNotifications.value.map(item => item.slug === updated.slug ? updated : item)
    if (apiDetailNotification.value?.slug === updated.slug) {
      apiDetailNotification.value = updated
    }
    await loadNotifications()
  } finally {
    markingNotificationsRead.value = false
  }
}

async function markAllNotificationsRead(): Promise<void> {
  if (!authStore.isAuthenticated) {
    return
  }

  markingNotificationsRead.value = true

  try {
    await api.post('/api/v1/community/notifications/read-all')
    apiNotifications.value = apiNotifications.value.map(item => ({ ...item, unread: false }))
    if (apiDetailNotification.value) {
      apiDetailNotification.value = { ...apiDetailNotification.value, unread: false }
    }
    await loadNotifications()
  } finally {
    markingNotificationsRead.value = false
  }
}

const notifications = computed(() => apiNotifications.value)
const notificationCounts = computed(() => ({
  total: notificationStats.value.total,
  unread: notificationStats.value.unread,
  announcements: notificationStats.value.announcements,
  labResults: notificationStats.value.lab_results,
  discussions: notificationStats.value.discussions,
  vendors: notificationStats.value.vendors,
}))
const notificationSummary = computed(() => notificationCategories.value.map(category => ({
  label: category.name,
  icon: category.icon,
  count: category.count,
  latest: `${category.count} published`,
})))
const notificationPaginationLabel = computed(() => notifications.value.length > 0 ? `Showing 1 to ${notifications.value.length} of ${notificationCounts.value.total} notifications` : 'Showing 0 notifications')
const currentNotificationSlug = computed(() => String(route.params.slug ?? ''))
const primaryNotification = computed(() => apiDetailNotification.value ?? notifications.value.find(item => item.slug === currentNotificationSlug.value) ?? null)
const currentNotificationIndex = computed(() => notifications.value.findIndex(item => item.slug === primaryNotification.value?.slug))
const previousNotification = computed(() => currentNotificationIndex.value > 0 ? notifications.value[currentNotificationIndex.value - 1] : null)
const nextNotification = computed(() => currentNotificationIndex.value >= 0 && currentNotificationIndex.value < notifications.value.length - 1 ? notifications.value[currentNotificationIndex.value + 1] : null)
const legalPage = computed(() => {
  if (page.value === 'legalPrivacy') {
    return {
      title: 'Privacy Policy',
      paragraphs: [
        'Peptide Vendors keeps account data, profile preferences, messages, submissions, and moderation records only for operating the private community.',
        'We use your information to authenticate access, show community content, support account settings, prevent abuse, and improve reliability.',
        'Do not post private medical, payment, or identifying information in public areas. You can manage profile visibility, notifications, privacy, sessions, and API tokens from account settings.',
      ],
    }
  }

  if (page.value === 'legalRules') {
    return {
      title: 'Community Rules',
      paragraphs: [
        'Keep discussion educational, evidence-led, and respectful. Personal attacks, harassment, spam, scams, and transaction coordination are not allowed.',
        'Vendor reviews and lab-result submissions should be specific, honest, and batch-aware where possible. Moderators may hide incomplete, unsafe, promotional, or unverifiable submissions.',
        'This community is for information sharing and harm-reduction context only. It is not medical advice, legal advice, or a marketplace.',
      ],
    }
  }

  return {
    title: 'Terms of Service',
    paragraphs: [
      'By using Peptide Vendors, you agree to use the community for lawful, educational discussion and to follow all community rules and moderation decisions.',
      'Content is provided by community members and administrators for informational purposes. You are responsible for verifying information before relying on it.',
      'Accounts may be restricted or removed for abuse, unsafe conduct, spam, evasion, or attempts to use the site as a marketplace.',
    ],
  }
})

function thumbnailStyle(index: number) {
  const positions = ['0% 0%', '50% 0%', '100% 0%', '0% 50%', '50% 50%', '100% 50%', '0% 100%', '50% 100%', '100% 100%']
  return {
    backgroundImage: `url(${researchImage})`,
    backgroundSize: '300% 300%',
    backgroundPosition: positions[index % positions.length] ?? '0% 0%',
  }
}

const PanelTrending = defineComponent({
  setup() {
    return () => h('article', { class: 'pv-panel' }, [
      h('h2', 'Trending Discussions'),
      h('div', { class: 'pv-ranked-list' }, discussions.value.slice(0, 5).map((topic, index) => h(RouterLink, { to: topic.href, class: 'pv-ranked-row' }, () => [h('span', { class: 'pv-rank' }, String(index + 1)), h('strong', topic.title), h('small', `${topic.replies} replies`)]))),
    ])
  },
})

const PaginationBlock = defineComponent({
  props: { label: { type: String, default: '' } },
  setup(props) {
    return () => props.label ? h('div', { class: 'pv-pagination' }, [h('span', props.label)]) : null
  },
})

const MemberProfile = defineComponent({
  props: { profile: { type: Object as PropType<UiMemberProfile>, required: true } },
  setup(props) {
    const badgeIcons = ['shield', 'flask', 'star', 'trophy']
    return () => {
      const profile = props.profile
      const stats = profile.stats
      const statCards: Array<[string, string, string]> = [
        ['Posts', formatCount(stats.posts), 'discussions'],
        ['Likes Received', formatCount(stats.likes), 'thumbs'],
        ['Solutions', formatCount(stats.solutions), 'shield'],
        ['Lab Reports', formatCount(stats.lab_reports ?? stats.guides), 'flask'],
        ['Thanks Received', formatCount(stats.thanks ?? stats.reviews), 'star'],
      ]

      return h('div', { class: 'pv-content-grid' }, [
        h('main', { class: 'pv-stack' }, [
          h('nav', { class: 'pv-breadcrumbs' }, `Members › ${profile.name}`),
          h('article', { class: ['pv-member-hero', profile.isModerator ? 'pv-member-hero--banner' : ''] }, [
            h('span', { class: ['pv-avatar', 'pv-avatar--xl', profile.color] }, profile.initial),
            h('div', { class: 'pv-topic-main' }, [h('h1', [profile.name, ' ', h('span', { class: 'pv-tag trusted' }, profile.role)]), h('p', `${profile.badge ?? profile.group} · Joined ${profile.joined} · Last seen ${profile.lastActive}`), h('p', profile.bio), h('span', { class: 'pv-chip-row' }, profile.interests.map(interest => h('span', interest)))]),
            h('div', { class: 'pv-member-actions' }, [h('button', { class: 'pv-primary-button', onClick: () => void startMessage(profile) }, [h(PvIcon, { name: 'message' }), ' Message']), h('button', { class: 'pv-small-button' }, [h(PvIcon, { name: 'users' }), ' Follow'])]),
          ]),
          h('div', { class: 'pv-metrics pv-metrics--member' }, statCards.map(stat => h('span', [h(PvIcon, { name: stat[2] }), h('strong', stat[1]), h('small', stat[0])]))),
          h('div', { class: 'pv-tabs pv-tabs--line' }, ['Overview', 'Activity', `Posts (${formatCount(stats.posts)})`, `Reviews (${formatCount(stats.reviews)})`, `Guides (${formatCount(stats.guides)})`, 'Badges'].map((tab, index) => h('button', { class: index === 0 ? 'active' : '' }, tab))),
          h('div', { class: 'pv-profile-grid' }, [
            h('article', { class: 'pv-panel' }, [h('h2', `About ${profile.name}`), h('p', profile.bio), h('dl', { class: 'pv-data-list' }, [h('div', [h('dt', 'Location'), h('dd', profile.location || '')]), h('div', [h('dt', 'Website'), h('dd', profile.websiteUrl ?? '')]), h('div', [h('dt', 'Member Since'), h('dd', profile.joined)]), h('div', [h('dt', 'Last Active'), h('dd', profile.lastActive)])])]),
            h('article', { class: 'pv-panel' }, [h('header', { class: 'pv-panel-header' }, [h('h2', 'Badges'), h('a', { class: 'pv-purple-link' }, 'View all')]), h('div', { class: 'pv-badge-grid' }, profile.badges.map((badge, i) => h('span', [h(PvIcon, { name: badgeIcons[i % badgeIcons.length] ?? 'shield' }), h('small', badge)])))]),
            h('article', { class: 'pv-panel' }, [h('h2', 'Recent Activity'), h('div', { class: 'pv-mini-list' }, profile.activities.map(activity => h('span', { class: 'pv-mini-row' }, [h(PvIcon, { name: activity.icon }), h('span', [h('strong', activity.title), h('small', activity.subtitle ? `${activity.subtitle} · ${activity.time}` : activity.time)])])))]),
          ]),
        ]),
        h('aside', { class: 'pv-stack' }, [
          h('article', { class: 'pv-panel' }, [h('h2', 'Reputation'), h('div', { class: 'pv-ring' }, [h('strong', formatCount(stats.reputation)), h('span', 'Reputation')])]),
          h('article', { class: 'pv-panel' }, [h('h2', 'Community Stats'), h('dl', { class: 'pv-data-list' }, [['Posts created', formatCount(stats.posts)], ['Likes received', formatCount(stats.likes)], ['Solutions provided', formatCount(stats.solutions)], ['Reports reviewed', formatCount(stats.lab_reports ?? stats.guides)]].map(row => h('div', [h('dt', row[0]), h('dd', row[1])])))]),
          h('article', { class: 'pv-panel' }, [h('h2', 'Following'), h('div', { class: 'pv-avatar-stack' }, onlineMembers.value.map(member => h('span', { class: ['pv-avatar', member.color] }, member.initial)).concat(h('span', { class: 'pv-more' }, `+${Math.max(0, memberStats.value.total - onlineMembers.value.length)}`)))]),
        ]),
      ])
    }
  },
})

const MessageBubble = defineComponent({
  props: {
    side: { type: String, required: true },
    text: { type: String, required: true },
    time: { type: String, required: true },
    file: { type: Boolean, default: false },
    attachmentName: { type: String, default: '' },
    attachmentLabel: { type: String, default: '' },
    avatarInitial: { type: String, default: '' },
    avatarColor: { type: String, default: 'purple' },
  },
  setup(props) {
    return () => h('div', { class: ['pv-message-bubble', `pv-message-bubble--${props.side}`] }, [
      props.side === 'in' ? h('span', { class: ['pv-avatar', props.avatarColor] }, props.avatarInitial) : null,
      h('div', [
        props.file ? h('span', { class: 'pv-file-card' }, [
          h(PvIcon, { name: 'document' }),
          h('strong', props.attachmentName),
          props.attachmentLabel ? h('small', props.attachmentLabel) : null,
        ]) : null,
        h('p', props.text),
        h('small', props.time),
      ]),
    ])
  },
})

const SettingsScreens = defineComponent({
  props: { page: { type: String, required: true } },
  setup(props) {
    const tabs: Array<[string, string, string, string]> = [['settingsProfile', '/settings', 'Profile', 'user'], ['settingsAccount', '/settings/account', 'Account', 'users'], ['settingsSecurity', '/settings/security', 'Security', 'shield'], ['settingsPrivacy', '/settings/privacy', 'Privacy', 'lock'], ['settingsNotifications', '/settings/notifications', 'Notifications', 'bell'], ['settingsPreferences', '/settings/preferences', 'Preferences', 'settings'], ['settingsBlocked', '/settings/blocked-users', 'Blocked Users', 'close'], ['settingsApi', '/settings/api-tokens', 'API Tokens', 'share'], ['settingsSessions', '/settings/sessions', 'Sessions', 'document'], ['settingsDanger', '/settings/danger-zone', 'Danger Zone', 'shield']]
    return () => h('div', { class: 'pv-settings-grid' }, [
      h('aside', { class: 'pv-settings-nav' }, [h('small', 'ACCOUNT SETTINGS'), ...tabs.map(tab => h(RouterLink, { to: tab[1], class: props.page === tab[0] ? 'active' : '' }, () => [h(PvIcon, { name: tab[3] }), tab[2]]))]),
      h('main', { class: 'pv-stack' }, [h('header', { class: 'pv-page-header' }, [h('div', [h('h1', props.page === 'settingsSecurity' ? 'Security' : props.page === 'settingsPrivacy' ? 'Privacy' : 'Account Settings'), h('p', props.page === 'settingsSecurity' ? 'Manage your password, login security and active sessions.' : props.page === 'settingsPrivacy' ? 'Manage your privacy settings and control how your information is shared.' : props.page === 'settingsAccount' ? 'Manage your account information and login preferences.' : 'Manage your account preferences and security settings.')])]), settingsMain(props.page)]),
      h('aside', { class: 'pv-stack' }, [settingsSummary(), props.page === 'settingsSecurity' ? tipsPanel('Security Tips', ['Use a strong password', 'Enable 2FA', 'Keep your email secure']) : props.page === 'settingsPrivacy' ? tipsPanel('Privacy Tips', ['Review your privacy settings', 'Be careful with shared info', 'Keep your account secure']) : quickActions(), h('article', { class: 'pv-panel' }, [h('h2', 'Need Help?'), h('p', 'Visit our Guides & FAQ section or contact the support team if you need assistance.'), h(RouterLink, { to: '/guides', class: 'pv-primary-button' }, () => 'Visit Guides & FAQ')])]),
    ])
  },
})

function settingsMain(pageName: string) {
  if (!authStore.isAuthenticated) {
    return h('article', { class: 'pv-panel' }, [
      h('h2', 'Log in required'),
      h('p', { class: 'pv-muted' }, 'Account settings are available after you log in.'),
      h(RouterLink, { to: '/login', class: 'pv-primary-button' }, () => 'Log In'),
    ])
  }

  const status = settingsStatusMessage.value
    ? h('p', { class: 'pv-muted' }, settingsStatusMessage.value)
    : null

  if (pageName === 'settingsSecurity') {
    return h('div', { class: 'pv-stack' }, [
      status,
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'lock' })]), h('div', [h('h2', 'Change Password'), h('div', { class: 'pv-input-group' }, [
        settingsInput('Current Password', passwordSettingsForm.value.current_password, value => { passwordSettingsForm.value.current_password = value }, 'password'),
        settingsInput('New Password', passwordSettingsForm.value.new_password, value => { passwordSettingsForm.value.new_password = value }, 'password'),
        settingsInput('Confirm New Password', passwordSettingsForm.value.new_password_confirmation, value => { passwordSettingsForm.value.new_password_confirmation = value }, 'password'),
      ]), h('button', { class: 'pv-primary-button', disabled: changingSettingsPassword.value, onClick: changeSettingsPassword }, changingSettingsPassword.value ? 'Updating...' : 'Update Password')])]),
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'shield' })]), h('div', [h('h2', 'Two-Factor Authentication (2FA)'), h('p', 'Add an extra layer of security to your account.'), h('span', { class: authUserValue('two_factor_enabled') ? 'pv-success-pill' : 'pv-tag' }, authUserValue('two_factor_enabled') ? 'Enabled' : 'Disabled')]), h('button', { class: 'pv-small-button' }, 'Manage 2FA')]),
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'document' })]), h('div', [h('h2', 'Login Sessions'), h('p', 'Manage your active sessions across different devices.'), sessionList()]), h(RouterLink, { to: '/settings/sessions', class: 'pv-small-button' }, () => 'View Sessions')]),
    ])
  }
  if (pageName === 'settingsPrivacy') {
    return h('div', { class: 'pv-stack' }, [
      status,
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'user' })]), h('div', [h('h2', 'Profile Visibility'), h('p', 'Choose who can view your profile.'), settingsRadioGroup('profile_visibility', [['everyone', 'Everyone'], ['members_only', 'Members Only'], ['nobody', 'Nobody']])])]),
      settingsSwitchCard('Show Online Status', 'Allow other members to see when you are online.', 'users', 'show_online'),
      h('article', { class: 'pv-panel pv-settings-card' }, [h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: 'message' })]), h('div', [h('h2', 'Direct Messages'), h('p', 'Control who can send you direct messages.'), settingsRadioGroup('direct_messages', [['everyone', 'Everyone'], ['members_only', 'Members Only'], ['nobody', 'Nobody']])])]),
      settingsSwitchCard('Activity Visibility', 'Display recent activity in community surfaces.', 'eye', 'show_recent_activity'),
      settingsSwitchCard('Data & Personalization', 'Personalize content and recommendations.', 'shield', 'personalize_experience'),
      settingsSwitchCard('Allow Analytics', 'Share anonymous usage data to improve the platform.', 'settings', 'allow_analytics'),
    ])
  }
  if (pageName === 'settingsAccount') {
    return h('article', { class: 'pv-panel pv-form-card' }, [
      h('h2', 'Account Information'),
      status,
      h('div', { class: 'pv-input-group' }, [
        settingsInput('Username', accountForm.value.username, value => { accountForm.value.username = value }),
        settingsInput('Display Name', accountForm.value.name, value => { accountForm.value.name = value }),
        settingsInput('Email Address', accountForm.value.email, () => {}, 'email', true),
      ]),
      h('p', [h('span', { class: authUserValue('email_verified_at') ? 'pv-tag trusted' : 'pv-tag' }, authUserValue('email_verified_at') ? 'Verified' : 'Unverified')]),
      h('h2', 'Preferences'),
      h('div', { class: 'pv-two-col' }, [
        settingsInput('Timezone', accountForm.value.timezone, value => { accountForm.value.timezone = value }),
        settingsInput('Language', userSettings.value.language, value => { userSettings.value.language = value }),
      ]),
      h('button', { class: 'pv-primary-button', disabled: savingSettings.value, onClick: async () => { await saveAccountProfile(); await saveUserSettings({ language: userSettings.value.language }) } }, savingSettings.value ? 'Saving...' : 'Save Account'),
      h('h2', 'Password'),
      h('div', { class: 'pv-input-group' }, [
        settingsInput('Current Password', passwordSettingsForm.value.current_password, value => { passwordSettingsForm.value.current_password = value }, 'password'),
        settingsInput('New Password', passwordSettingsForm.value.new_password, value => { passwordSettingsForm.value.new_password = value }, 'password'),
        settingsInput('Confirm New Password', passwordSettingsForm.value.new_password_confirmation, value => { passwordSettingsForm.value.new_password_confirmation = value }, 'password'),
      ]),
      h('button', { class: 'pv-primary-button', disabled: changingSettingsPassword.value, onClick: changeSettingsPassword }, changingSettingsPassword.value ? 'Updating...' : 'Update Password'),
    ])
  }
  if (pageName === 'settingsNotifications') {
    return settingsOptionPanel('Notification Settings', [
      ['Email notifications', 'email_notifications'],
      ['Push notifications', 'push_notifications'],
      ['Sound effects', 'sound_enabled'],
    ])
  }
  if (pageName === 'settingsPreferences') {
    return settingsOptionPanel('Preferences', [
      ['Compact discussion lists', 'compact_discussions'],
      ['Show online members', 'show_online_members'],
      ['Remember content filters', 'remember_content_filters'],
    ])
  }
  if (pageName === 'settingsBlocked') {
    return h('article', { class: 'pv-panel' }, [h('h2', 'Blocked Users'), h('p', { class: 'pv-muted' }, 'Blocked users will appear here once you block someone from the community.'), h('div', { class: 'pv-mini-list' }, [])])
  }
  if (pageName === 'settingsApi') {
    return h('article', { class: 'pv-panel' }, [
      h('header', { class: 'pv-panel-header' }, [h('h2', 'API Tokens')]),
      status,
      h('div', { class: 'pv-two-col' }, [
        settingsInput('Token Name', apiTokenForm.value.name, value => { apiTokenForm.value.name = value }),
        h('button', { class: 'pv-primary-button', disabled: !apiTokenForm.value.name.trim(), onClick: createApiToken }, 'Create Token'),
      ]),
      newPlainApiToken.value ? h('p', { class: 'pv-muted' }, `New token: ${newPlainApiToken.value}`) : null,
      h('div', { class: 'pv-mini-list' }, userApiTokens.value.length > 0
        ? userApiTokens.value.map(token => h('span', { class: 'pv-mini-row' }, [
          h(PvIcon, { name: 'document' }),
          h('span', [h('strong', token.name), h('small', token.last_used_at ? `Last used ${formatDate(token.last_used_at)}` : `Created ${formatDate(token.created_at ?? '')}`)]),
          h('button', { class: 'pv-small-button', onClick: () => deleteApiToken(token.id) }, 'Revoke'),
        ]))
        : [h('p', { class: 'pv-muted' }, 'No personal API tokens have been created yet.')]),
    ])
  }
  if (pageName === 'settingsSessions') {
    return h('article', { class: 'pv-panel' }, [h('h2', 'Sessions'), status, sessionList()])
  }
  if (pageName === 'settingsDanger') {
    return h('article', { class: 'pv-panel' }, [h('h2', 'Danger Zone'), h('p', { class: 'pv-muted' }, 'Account deactivation actions require authentication and confirmation.'), h('button', { class: 'pv-small-button' }, 'Deactivate Account')])
  }
  return h('article', { class: 'pv-panel pv-form-card' }, [
    h('h2', 'Profile Information'),
    status,
    h('div', { class: 'pv-profile-form-head' }, [h('span', { class: 'pv-avatar pv-avatar--xl purple' }, accountInitial()), h('div', { class: 'pv-input-group' }, [
      settingsInput('Username', accountForm.value.username, value => { accountForm.value.username = value }),
      settingsInput('Display Name', accountForm.value.name, value => { accountForm.value.name = value }),
    ])]),
    h('label', ['Email Address', h('input', { value: accountForm.value.email, disabled: true })]),
    settingsTextarea('Bio', accountForm.value.bio, value => { accountForm.value.bio = value }),
    h('div', { class: 'pv-two-col' }, [
      settingsInput('Timezone', accountForm.value.timezone, value => { accountForm.value.timezone = value }),
      settingsInput('Website', accountForm.value.website_url, value => { accountForm.value.website_url = value }),
    ]),
    h('button', { class: 'pv-primary-button', disabled: savingSettings.value, onClick: saveAccountProfile }, savingSettings.value ? 'Saving...' : 'Save Changes'),
    h('hr'),
    h('h2', 'Profile Photo'),
    h('input', { ref: (el) => { avatarFileInput.value = el as HTMLInputElement | null }, type: 'file', accept: 'image/*', style: 'display:none', onChange: uploadProfileAvatar }),
    h('button', { class: 'pv-small-button', disabled: uploadingAvatar.value, onClick: () => avatarFileInput.value?.click() }, [h(PvIcon, { name: 'upload' }), uploadingAvatar.value ? ' Uploading...' : ' Change Photo']),
    h('h2', 'Banner Image'),
    h('button', { class: 'pv-small-button' }, [h(PvIcon, { name: 'image' }), ' Change Banner']),
  ])
}

function settingsInput(label: string, value: string, onValue: (value: string) => void, type = 'text', disabled = false) {
  return h('label', [label, h('input', { type, placeholder: label, value, disabled, onInput: (event: Event) => onValue((event.target as HTMLInputElement).value) })])
}

function settingsTextarea(label: string, value: string, onValue: (value: string) => void) {
  return h('label', [label, h('textarea', { value, onInput: (event: Event) => onValue((event.target as HTMLTextAreaElement).value) })])
}

function settingsSwitchCard(title: string, text: string, icon: string, key: BooleanUserSettingKey) {
  return h('article', { class: 'pv-panel pv-settings-card' }, [
    h('span', { class: 'pv-icon-tile' }, [h(PvIcon, { name: icon })]),
    h('div', [h('h2', title), h('p', text)]),
    h('button', { class: 'pv-toggle-button', onClick: () => toggleUserSetting(key) }, [
      h('span', { class: userSettings.value[key] ? 'pv-switch active' : 'pv-switch' }),
    ]),
  ])
}

function settingsRadioGroup(key: 'profile_visibility' | 'direct_messages', options: Array<[UserSettingsPayload[typeof key], string]>) {
  return h('div', { class: 'pv-radio-list' }, options.map(([value, label]) => h('label', [
    h('input', { type: 'radio', checked: userSettings.value[key] === value, onChange: () => setUserSetting(key, value) }),
    label,
  ])))
}

function sessionList() {
  if (userSessions.value.length === 0) {
    return h('p', { class: 'pv-muted' }, 'No active sessions or tokens were found.')
  }

  return h('div', { class: 'pv-mini-list' }, userSessions.value.slice(0, 6).map(session => h('span', { class: 'pv-mini-row' }, [
    h(PvIcon, { name: 'document' }),
    h('span', [h('strong', session.name ?? session.userAgent ?? 'Authenticated session'), h('small', session.lastActivity ? formatDate(session.lastActivity) : 'Active')]),
    session.isCurrent ? h('em', { class: 'pv-tag trusted' }, 'Current') : null,
  ])))
}

function settingsSummary() {
  return h('article', { class: 'pv-panel' }, [h('h2', 'Account Summary'), h('div', { class: 'pv-summary-user' }, [h('span', { class: 'pv-avatar pv-avatar--xl purple' }, accountInitial()), h('div', [h('strong', accountName()), h('span', { class: 'pv-tag' }, accountRole())])]), h('dl', { class: 'pv-data-list' }, [['Joined', authUserDate('created_at') || 'Not signed in'], ['Last Active', authUserDate('last_active') || ''], ['Email', accountEmail()], ['Two-Factor Authentication', authUserValue('two_factor_enabled') ? 'Enabled' : 'Disabled']].map(row => h('div', [h('dt', row[0]), h('dd', row[1])])))])
}

function quickActions() {
  return h('article', { class: 'pv-panel' }, [h('h2', 'Quick Actions'), h('div', { class: 'pv-filter-list' }, ['Change Password', 'Two-Factor Authentication', 'Download My Data', 'Deactivate Account'].map(action => h('button', [h(PvIcon, { name: 'shield' }), action, h(PvIcon, { name: 'chevron' })])))])
}

function settingsOptionPanel(title: string, options: Array<[string, BooleanUserSettingKey]>) {
  return h('article', { class: 'pv-panel' }, [
    h('h2', title),
    settingsStatusMessage.value ? h('p', { class: 'pv-muted' }, settingsStatusMessage.value) : null,
    h('div', { class: 'pv-stack' }, options.map(option => h('div', { class: 'pv-toggle-row' }, [
      h('span', [h('strong', option[0]), h('small', userSettings.value[option[1]] ? 'Enabled' : 'Disabled')]),
      h('button', { class: 'pv-toggle-button', disabled: savingSettings.value, onClick: () => toggleUserSetting(option[1]) }, [
        h('span', { class: userSettings.value[option[1]] ? 'pv-switch active' : 'pv-switch' }),
      ]),
    ]))),
  ])
}

function tipsPanel(title: string, tips: string[]) {
  return h('article', { class: 'pv-panel' }, [
    h('h2', title),
    h('div', { class: 'pv-mini-list' }, tips.map(tip => h('span', { class: 'pv-mini-row' }, [
      h(PvIcon, { name: 'shield' }),
      h('span', [
        h('strong', tip),
        h('small', 'Simple steps to keep your account safe.'),
      ]),
    ]))),
  ])
}

function authUserValue(key: string): any {
  const user = authStore.user as Record<string, any> | null
  return user?.[key] ?? null
}

function accountName(): string {
  return String(authUserValue('username') || authUserValue('name') || 'Not signed in')
}

function accountEmail(): string {
  return String(authUserValue('email') || 'Not signed in')
}

function accountInitial(): string {
  return accountName().charAt(0).toUpperCase()
}

function accountRole(): string {
  const roles = authUserValue('roles')
  return Array.isArray(roles) && roles[0] ? String(roles[0]) : ''
}

function authUserDate(key: string): string {
  const value = authUserValue(key)
  if (!value) {
    return ''
  }

  return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' })
}
</script>
