<template>
<section class="pv-page">
    <template v-if="showUpgradePrompt && detailVendor?.tier === 'premium'">
      <div class="pv-upgrade-overlay">
        <div class="pv-upgrade-card">
          <span class="pv-upgrade-icon"><PvIcon name="shield" /></span>
          <h2>Premium Feature</h2>
          <p>Upgrade to Premium to read and write vendor reviews with ratings, photos, and detailed feedback.</p>
          <router-link to="/pricing" class="pv-primary-button">View Pricing</router-link>
        </div>
      </div>
    </template>
    <div v-else-if="detailVendor" class="pv-content-grid pv-content-grid--vendor-detail">
      <main class="pv-stack">
        <router-link to="/vendor-reviews" class="op-back">← Back to Vendors</router-link>
        <article class="vendor-card vendor-card--hero">
          <div v-if="detailVendor.imageUrl" class="vendor-hero-bg" :style="{ backgroundImage: `url(${detailVendor.imageUrl})` }"></div>
          <div class="vendor-top">
              <span v-if="detailVendor.imageUrl" class="vendor-logo"><img :src="detailVendor.imageUrl" :alt="detailVendor.name"></span>
              <span v-else class="vendor-logo vendor-logo--letter">{{ detailVendor.logoText }}</span>
              <div class="vendor-info">
                <h3>{{ detailVendor.name }} <span v-if="detailVendor.tier === 'premium'" class="pv-tier-badge pv-tier-badge--premium">Premium</span></h3>
              <span class="trusted"><PvIcon name="check" /> {{ detailVendor.status }}</span>
              <span v-if="detailVendor.country" class="country-badge">{{ countryFlag(detailVendor.country) }} {{ detailVendor.country }}</span>
              <p class="vendor-meta"><PvIcon name="star" /> {{ detailVendor.rating }} / 5 <span>{{ detailVendor.reviews }} {{ detailVendor.reviews === 1 ? 'review' : 'reviews' }}</span><span>Member since {{ detailVendor.since || 'recently' }}</span></p>
              <div class="vendor-tags" style="margin-top:8px">
                <span v-for="chip in detailVendor.chips" :key="chip">{{ chip }}</span>
              </div>
            </div>
          </div>
          <div class="vendor-actions" style="margin-top:16px">
            <router-link :to="`${detailVendor.href}/review`" class="primary"><PvIcon name="edit" /> Write Review</router-link>
            <button v-if="hasVendorContact(detailVendor)" class="secondary" @click="showVendorContactSection">Contact</button>
            <router-link v-if="detailVendor.isOwnedByViewer" to="/vendor-portal" class="secondary">Manage</router-link>
            <a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="secondary">Website</a>
          </div>
        </article>
        <div class="pv-tabs pv-tabs--line"><button :class="{ active: vendorDetailTab === 'overview' }" @click="vendorDetailTab = 'overview'">Overview</button><button :class="{ active: vendorDetailTab === 'reviews' }" @click="vendorDetailTab = 'reviews'">Reviews ({{ detailVendor.reviews }})</button><button :class="{ active: vendorDetailTab === 'products' }" @click="vendorDetailTab = 'products'">Products ({{ detailVendor.productCount }})</button><button :class="{ active: vendorDetailTab === 'documents' }" @click="vendorDetailTab = 'documents'">Documents ({{ detailVendor.documents.length }})</button><button :class="{ active: vendorDetailTab === 'about' }" @click="vendorDetailTab = 'about'">About</button></div>
        <article v-if="vendorDetailTab === 'overview'" class="pv-panel pv-review-summary">
          <div class="pv-score-block"><strong>{{ detailVendor.rating }}</strong><span class="pv-stars">★★★★★</span><small>{{ detailVendor.reviews }} reviews</small></div>
          <div class="pv-bars pv-bars--wide"><span v-for="row in detailVendor.ratingDistribution" :key="row.rating">{{ row.rating }} stars <b :style="{ '--w': `${row.percent}%` }"></b><em>{{ row.percent }}% ({{ row.count }})</em></span></div>
          <div class="pv-rate-box"><h3>Rate this vendor</h3><p>Share your experience to help others in the community.</p><div class="pv-stars pv-stars--big">☆☆☆☆☆</div><router-link :to="`${detailVendor.href}/review`" class="pv-primary-button pv-full">Write a Review</router-link></div>
        </article>
        <template v-if="vendorDetailTab === 'products'">
          <article class="pv-panel pv-product-catalog-panel">
            <header class="pv-panel-header">
              <div>
                <h2>Product Catalog</h2>
                <p class="pv-muted">{{ detailVendor.productCount }} products listed. Purchases happen off-site through vendor contact only.</p>
              </div>
              <button v-if="hasVendorContact(detailVendor)" class="pv-small-button" type="button" @click="showVendorContactSection"><PvIcon name="mail" /> Contact Vendor</button>
            </header>
            <div class="pv-toolbar pv-product-toolbar">
              <label class="pv-input-search">
                <input v-model="vendorProductSearch" placeholder="Search products..." type="search">
                <PvIcon name="search" />
              </label>
              <label class="pv-compact-select">Category<select v-model="vendorProductCategoryFilter"><option value="">All Categories</option><option v-for="category in vendorProductCategoryOptions" :key="category" :value="category">{{ category }}</option></select></label>
              <label class="pv-compact-select">Availability<select v-model="vendorProductAvailabilityFilter"><option value="">All Availability</option><option value="in_stock">In stock</option><option value="limited">Limited</option><option value="out_of_stock">Out of stock</option></select></label>
              <label class="pv-compact-select">Sort<select v-model="vendorProductSort"><option value="featured">Featured</option><option value="price-low">Price low to high</option><option value="price-high">Price high to low</option><option value="name">Name A-Z</option></select></label>
            </div>
            <p v-if="actionStatusMessage" class="pv-alert pv-alert--compact">{{ actionStatusMessage }}</p>
            <p v-if="filteredVendorProducts.length === 0" class="pv-muted">No public products match these filters.</p>
            <div class="pv-product-grid">
              <article v-for="product in filteredVendorProducts" :key="product.id ?? product.slug" class="pv-product-card">
                <header class="pv-product-card-top">
                  <span class="pv-product-stock" :class="product.availability === 'out_of_stock' ? 'avoid' : product.availability === 'limited' ? 'caution' : 'trusted'"><i></i>{{ product.availabilityLabel }}</span>
                  <button class="pv-product-save-button" :class="{ active: isBookmarkedProduct(product) }" type="button" :aria-label="isBookmarkedProduct(product) ? 'Remove product bookmark' : 'Bookmark product'" @click="toggleProductBookmark(product)">
                    <PvIcon name="bookmark" />
                  </button>
                </header>
                <div class="pv-product-card-body">
                  <span class="pv-product-card-image"><img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name"><PvIcon v-else name="flask" /></span>
                  <div class="pv-product-card-info">
                    <span class="pv-product-kind">{{ product.category || 'Product' }}</span>
                    <h3>{{ product.name }}</h3>
                    <div class="pv-product-specs">
                      <span><PvIcon name="flask" /><strong>{{ product.strength || 'Ask' }}</strong><small>Strength</small></span>
                      <span><PvIcon name="box" /><strong>{{ product.packageSize || 'Ask' }}</strong><small>Quantity</small></span>
                      <span><PvIcon name="tag" /><strong>{{ product.category || 'Product' }}</strong><small>Type</small></span>
                    </div>
                    <p v-if="product.description" class="pv-product-description">{{ product.description }}</p>
                    <div class="pv-product-variant-tiles">
                      <span v-for="(variant, index) in product.variants" :key="`${variant.label}-${index}`" :class="{ active: index === 0 }" :title="`${variant.label} ${variantPrice(variant)}`">
                        <strong>{{ variant.label }}</strong>
                        <em>{{ variantPrice(variant) }}</em>
                      </span>
                      <span v-if="!product.variants.length" class="active">
                        <strong>{{ product.strength || product.packageSize || 'Listing' }}</strong>
                        <em>{{ product.priceLabel || 'Contact for price' }}</em>
                      </span>
                    </div>
                    <span class="pv-product-review-note"><PvIcon name="star" /><template v-if="product.reviews > 0">{{ product.ratingLabel }} / 5 · {{ product.reviews }} {{ product.reviews === 1 ? 'review' : 'reviews' }}</template><template v-else>No product reviews yet</template></span>
                    <span v-if="product.tags.length" class="pv-chip-row pv-product-chip-row"><span v-for="tag in product.tags.slice(0, 4)" :key="tag">{{ tag }}</span></span>
                  </div>
                </div>
                <footer class="pv-product-card-footer">
                  <strong>{{ productCatalogPriceLabel(product) }}</strong>
                  <span><PvIcon name="shield" /> Verified listing</span>
                  <button class="pv-primary-button" type="button" @click="showVendorContactSection"><PvIcon name="mail" /> Contact Vendor</button>
                </footer>
                <div class="pv-product-trust-strip">
                  <span><PvIcon name="mail" /><strong>Direct Contact</strong><small>No on-site checkout</small></span>
                  <span><PvIcon name="shield" /><strong>Trusted Vendor</strong><small>Reviewed profile</small></span>
                  <span><PvIcon name="lock" /><strong>Research Use Only</strong><small>Not for human use</small></span>
                </div>
              </article>
            </div>
          </article>
          <article class="pv-alert pv-alert--compact"><PvIcon name="shield" /> Product listings are informational only. This site does not process orders, carts, payments, shipping, refunds, or transactions.</article>
        </template>
        <template v-if="vendorDetailTab === 'documents'">
          <article class="pv-panel pv-doc-list-panel">
            <header class="pv-panel-header">
              <div>
                <h2>Documents</h2>
                <p class="pv-muted">Certificates of analysis, quality reports, and other supporting documents from {{ detailVendor.name }}.</p>
              </div>
            </header>
            <p v-if="detailVendor.documents.length === 0" class="pv-muted">No documents have been published yet.</p>
            <div v-else class="pv-doc-list">
              <article v-for="doc in detailVendor.documents" :key="doc.id" class="pv-doc-row">
                <span class="pv-doc-type-icon">
                  <PvIcon :name="doc.fileType === 'image' ? 'image' : 'document'" />
                </span>
                <div class="pv-doc-info">
                  <strong>{{ doc.title }}</strong>
                  <span class="pv-doc-meta">
                    <span v-if="doc.category" class="pv-tag" :class="doc.category === 'coa' ? 'trusted' : 'caution'">{{ doc.category.toUpperCase() }}</span>
                    <span>{{ doc.fileType.toUpperCase() }}</span>
                    <span v-if="doc.description">{{ doc.description }}</span>
                  </span>
                </div>
                <a :href="doc.url" target="_blank" rel="noreferrer" class="pv-primary-button">
                  <PvIcon name="download" /> Download
                </a>
              </article>
            </div>
          </article>
        </template>
        <template v-if="vendorDetailTab === 'reviews'">
          <div class="pv-toolbar pv-vendor-review-toolbar">
            <label class="pv-compact-select">Rating<select v-model="vendorReviewRatingFilter"><option value="">All Ratings</option><option v-for="rating in [5, 4, 3, 2, 1]" :key="rating" :value="String(rating)">{{ rating }} stars</option></select></label>
            <label class="pv-compact-select">Product<select v-model="vendorReviewProductFilter"><option value="">All Products</option><option v-for="product in vendorReviewProductOptions" :key="product" :value="product">{{ product }}</option></select></label>
            <label class="pv-compact-select">When<select v-model="vendorReviewTimeFilter"><option value="all">All Time</option><option value="recent">Recent First</option></select></label>
            <span class="pv-flex-spacer"></span>
            <button class="pv-small-button" type="button" @click="toggleVendorReviewSort">{{ vendorReviewSortLabel }} <PvIcon name="chevron" /></button>
          </div>
          <p v-if="vendorReviewStatusMessage" class="pv-alert pv-alert--compact">{{ vendorReviewStatusMessage }}</p>
          <p v-if="reviews.length === 0" class="pv-muted">No published reviews yet.</p>
          <article v-for="review in reviews" :key="review.id ?? review.author" class="pv-review-row">
            <span class="pv-avatar" :class="review.color">{{ review.initial }}</span>
            <div>
              <div class="pv-reply-head"><strong>{{ review.author }}</strong><span class="pv-tag" :class="review.verifiedBuyer ? 'trusted' : 'caution'">{{ review.verifiedBuyer ? 'Verified Buyer' : 'Community Review' }}</span><span>{{ review.date }}</span></div>
              <span class="pv-stars">★★★★★ <em>{{ review.rating }}/5</em></span>
              <h3>{{ review.title }}</h3>
              <p>{{ review.text }}</p>
              <span class="pv-chip-row"><span v-for="chip in review.chips" :key="chip">{{ chip }}</span></span>
              <div v-if="review.photoUrls.length" class="pv-review-photo-grid">
                <a v-for="photo in review.photoUrls" :key="photo" :href="photo" target="_blank" rel="noreferrer">
                  <img :src="photo" alt="Review photo">
                </a>
              </div>
              <div v-if="review.vendorResponse" class="pv-vendor-response">
                <strong>Vendor Response</strong>
                <p>{{ review.vendorResponse }}</p>
                <small v-if="review.respondedAt">Responded {{ formatDate(review.respondedAt) }}</small>
              </div>
              <div v-if="detailVendor?.isOwnedByViewer && !review.vendorResponse && respondingReviewId !== review.id" style="margin-top:8px">
                <button class="pv-small-button" type="button" @click="respondingReviewId = review.id; reviewResponseText = ''">Respond</button>
              </div>
              <div v-if="respondingReviewId === review.id" class="pv-vendor-respond-form">
                <textarea v-model="reviewResponseText" maxlength="2000" rows="3" placeholder="Write your public response..." class="pv-vendor-response-textarea"></textarea>
                <div style="display:flex;gap:6px;margin-top:6px">
                  <button class="pv-primary-button" :disabled="submittingReviewResponse" @click="respondToReview(review)">{{ submittingReviewResponse ? 'Posting...' : 'Submit Response' }}</button>
                  <button class="pv-small-button" type="button" @click="cancelVendorResponse">Cancel</button>
                </div>
              </div>
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
          <div v-if="hasVendorContact(detailVendor)" class="pv-contact-list">
            <a v-for="link in detailVendorContactLinks" :key="`${link.label}-${link.value}`" :href="link.href" target="_blank" rel="noreferrer" class="pv-contact-row">
              <PvIcon :name="link.icon" />
              <span><strong>{{ link.label }}</strong><small>{{ link.value }}</small></span>
              <small class="pv-open-link">Open</small>
            </a>
            <p v-if="detailVendor.contact.responsePolicy" class="pv-muted"><strong>Response policy:</strong> {{ detailVendor.contact.responsePolicy }}</p>
            <p v-if="detailVendor.contact.publicNotes" class="pv-muted">{{ detailVendor.contact.publicNotes }}</p>
          </div>
          <a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="pv-small-button">Visit Website <PvIcon name="share" /></a>
        </article>
      </main>
      <aside class="pv-stack">
        <article class="pv-panel"><h2>Vendor Summary</h2><dl class="pv-data-list"><div><dt>Total Reviews</dt><dd>{{ detailVendor.reviews }}</dd></div><div><dt>Average Rating</dt><dd><span class="pv-stars">★</span> {{ detailVendor.rating }} / 5</dd></div><div><dt>Would Buy Again</dt><dd>{{ detailVendor.buyAgain }}</dd></div><div><dt>Response Rate</dt><dd>{{ detailVendor.responseRate }}</dd></div><div><dt>Avg. Response Time</dt><dd>{{ detailVendor.avgResponseTime }}</dd></div></dl></article>
        <article v-if="hasVendorContact(detailVendor)" class="pv-panel">
          <h2>Contact & Support</h2>
          <div class="pv-contact-list">
            <a v-for="link in detailVendorContactLinks" :key="`${link.label}-sidebar-${link.value}`" :href="link.href" target="_blank" rel="noreferrer" class="pv-contact-row">
              <PvIcon :name="link.icon" />
              <span><strong>{{ link.label }}</strong><small>{{ link.value }}</small></span>
              <small class="pv-open-link">Open</small>
            </a>
          </div>
          <p v-if="detailVendor.contact.responsePolicy" class="pv-muted">{{ detailVendor.contact.responsePolicy }}</p>
          <p v-if="detailVendor.contact.publicNotes" class="pv-muted">{{ detailVendor.contact.publicNotes }}</p>
          <p class="pv-muted">Vendor contact details are for support and profile verification context only.</p>
        </article>
        <article class="pv-panel"><h2>Top Products</h2><div class="pv-ranked-list"><span v-for="product in detailVendor.topProducts" :key="product.slug || product.name" class="pv-ranked-row"><span class="pv-vendor-logo"><img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name"><PvIcon v-else name="flask" /></span><strong>{{ product.name }}</strong><span>{{ product.priceLabel || product.availabilityLabel }}</span></span></div><button class="pv-small-button pv-full" @click="vendorDetailTab = 'products'">View all products</button></article>
        <article class="pv-panel"><h2>Active Discussions</h2><div class="pv-mini-list"><router-link v-for="topic in discussions.slice(0, 3)" :key="topic.title" :to="topic.href" class="pv-mini-row"><PvIcon name="discussions" /><span><strong>{{ topic.title }}</strong></span><span>{{ topic.replies }}</span></router-link></div><router-link class="pv-small-button pv-full" to="/discussions">View all discussions</router-link></article>
        <article class="pv-panel"><h2>About {{ detailVendor.name }}</h2><p class="pv-muted">{{ detailVendor.description }}</p><a v-if="detailVendor.websiteUrl" :href="detailVendor.websiteUrl" target="_blank" rel="noreferrer" class="pv-small-button">Visit Website <PvIcon name="share" /></a></article>
      </aside>
    </div>
    <div v-else class="pv-empty-route">
      <h1>Vendor not found</h1>
      <p>This vendor has not been published or does not exist.</p>
    </div>
    <div v-if="page === 'reviewModal' && detailVendor && detailVendor.tier !== 'premium'" class="pv-modal-backdrop">
      <form v-if="detailVendor" class="pv-modal" @submit.prevent="submitVendorReview">
        <header class="pv-panel-header"><div><h2>Write a Review</h2><p class="pv-muted">Reviewing {{ detailVendor.name }}. Share your experience to help others in the community.</p></div><router-link :to="detailVendor.href" class="pv-icon-button pv-icon-button--static"><PvIcon name="close" /></router-link></header>
        <p v-if="vendorReviewFormError" class="pv-alert pv-alert--compact">{{ vendorReviewFormError }}</p>
         <label>Overall Rating *
           <div class="pv-star-rating">
             <button type="button" v-for="star in 5" :key="star" :class="{ active: star <= newVendorReview.rating }" @click="newVendorReview.rating = star">★</button>
           </div>
         </label>
        <label>Review Title *<input v-model="newVendorReview.title" required maxlength="160" placeholder="Summarise your experience in a few words..."><small>{{ newVendorReview.title.length }}/160</small></label>
        <label>Your Review *
  <div class="pv-textarea-with-emoji">
    <textarea v-model="newVendorReview.body" required maxlength="4000" placeholder="Tell others about your experience with this vendor. Include details about product quality, shipping, customer service, packaging, etc."></textarea>
    <EmojiPicker v-model="newVendorReview.body" />
  </div>
  <small>{{ newVendorReview.body.length }}/4000</small></label>
         <div><strong>Would you buy from this vendor again?</strong><div class="pv-choice-row"><button type="button" :class="{ active: newVendorReview.would_buy_again }" @click="newVendorReview.would_buy_again = true"><PvIcon name="thumbs" /> Yes, I would</button><button type="button" :class="{ active: !newVendorReview.would_buy_again }" @click="newVendorReview.would_buy_again = false">No, I wouldn&apos;t</button></div></div>
         <label>Review Photos *
           <p class="pv-muted" style="font-size:13px">You must include a photo of the received product with a handwritten note showing your <strong>username</strong> and the <strong>date received</strong>. Cover any personal info before uploading.</p>
           <input ref="vendorReviewPhotoInput" type="file" accept="image/*" multiple class="pv-sr-only" @change="selectVendorReviewPhotos">
           <button class="pv-upload-box" type="button" @click="vendorReviewPhotoInput?.click()"><PvIcon name="image" /> Click to upload photos<br><small>PNG, JPG up to 5MB each (min 1 photo required)</small></button>
         </label>
        <div v-if="vendorReviewPhotoPreviews.length" class="pv-upload-preview-grid">
          <span v-for="(preview, index) in vendorReviewPhotoPreviews" :key="preview">
            <img :src="preview" :alt="`Review photo ${Number(index) + 1}`">
            <button type="button" aria-label="Remove photo" @click="removeVendorReviewPhoto(index)"><PvIcon name="close" /></button>
          </span>
        </div>
        <footer><router-link :to="detailVendor.href" class="pv-small-button">Cancel</router-link><button class="pv-primary-button" type="submit" :disabled="submittingVendorReview">{{ submittingVendorReview ? 'Submitting...' : 'Submit Review' }}</button></footer>
      </form>
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
