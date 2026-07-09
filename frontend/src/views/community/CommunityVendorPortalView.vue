<template>
<section class="pv-page">
    <div class="pv-content-grid">
      <main class="pv-stack">
        <header class="pv-page-header">
          <div>
            <h1>Vendor Portal</h1>
            <p>Manage your approved vendor listing and public contact details.</p>
          </div>
          <router-link v-if="apiMyVendor && apiMyVendor.publishStatus === 'published'" :to="apiMyVendor.href" class="pv-small-button">
            View Public Profile <PvIcon name="chevron" />
          </router-link>
        </header>

        <article v-if="!authStore.isAuthenticated" class="pv-panel pv-vendor-access-card">
          <span class="pv-icon-tile"><PvIcon name="lock" /></span>
          <div>
            <h2>Sign in for vendor access</h2>
            <p class="pv-muted">Use an account to request vendor access, manage an approved profile, and maintain public product listings.</p>
          </div>
          <div class="pv-reply-login-actions">
            <router-link :to="{ path: '/login', query: { redirect: route.fullPath } }" class="pv-primary-button">Sign In</router-link>
            <router-link :to="{ path: '/register', query: { redirect: route.fullPath } }" class="pv-small-button">Register</router-link>
          </div>
        </article>

        <article v-if="authStore.isAuthenticated && apiMyVendor" class="pv-panel pv-vendor-owner-card">
          <span class="pv-logo-card" :class="apiMyVendor.class"><img v-if="apiMyVendor.imageUrl" :src="apiMyVendor.imageUrl" :alt="apiMyVendor.name"><template v-else>{{ apiMyVendor.logoText }}</template></span>
          <span>
            <strong>{{ vendorPortalAccessApproved ? `You control ${apiMyVendor.name}` : `${apiMyVendor.name} is locked` }}</strong>
            <small>{{ apiMyVendor.publishStatus }} profile · {{ vendorPortalAccessApproved ? 'approved vendor' : 'vendor access disabled' }}</small>
          </span>
          <router-link v-if="apiMyVendor.publishStatus === 'published'" :to="apiMyVendor.href" class="pv-small-button">Open Profile</router-link>
        </article>

        <p v-if="authStore.isAuthenticated && vendorPortalStatusMessage" class="pv-alert pv-alert--compact">{{ vendorPortalStatusMessage }}</p>
        <p v-if="authStore.isAuthenticated && vendorPortalFormError" class="pv-alert pv-alert--compact">{{ vendorPortalFormError }}</p>

        <form v-if="authStore.isAuthenticated && vendorPortalAccessApproved" class="pv-form-card pv-vendor-portal-form" @submit.prevent="saveVendorProfile">
          <header class="pv-panel-header">
            <div>
              <h2>{{ apiMyVendor ? 'Edit Vendor Profile' : 'Set Up Vendor Profile' }}</h2>
              <p class="pv-muted">This information appears on your vendor review page for contact and support only.</p>
            </div>
            <span v-if="vendorPortalLoaded" class="pv-tag trusted">approved</span>
          </header>

          <div class="pv-two-col">
            <label>
               Vendor Name *
               <input v-model="vendorPortalForm.name" required maxlength="160" placeholder="Public vendor name">
             </label>
           </div>
          <label>
            About
            <textarea v-model="vendorPortalForm.description" maxlength="4000" rows="5" placeholder="Describe support expectations, service area, and public profile details."></textarea>
          </label>
          <label>
            Tags
            <input v-model="vendorPortalForm.tags" placeholder="Comma-separated tags, e.g. Domestic, Lab Tested">
          </label>

          <div class="pv-two-col">
            <label>
              Website
              <input v-model="vendorPortalForm.website_url" type="url" maxlength="255" placeholder="https://example.com">
            </label>
            <div class="pv-upload-control">
              <strong>Vendor Image</strong>
              <div class="pv-image-upload-row">
                <span class="pv-logo-card" :class="apiMyVendor?.class"><img v-if="vendorPortalForm.image_url" :src="assetUrl(vendorPortalForm.image_url)" alt="Vendor image preview"><template v-else>{{ apiMyVendor?.logoText ?? 'V' }}</template></span>
                <span>
                  <small>Upload a logo or product-safe vendor image up to 5 MB.</small>
                  <input ref="vendorImageFileInput" type="file" accept="image/*" class="pv-sr-only" @change="uploadVendorImage">
                  <button class="pv-small-button" type="button" :disabled="uploadingVendorImage" @click="vendorImageFileInput?.click()"><PvIcon name="upload" /> {{ uploadingVendorImage ? 'Uploading...' : 'Upload Image' }}</button>
                </span>
              </div>
            </div>
          </div>
          <div class="pv-two-col">
            <label>
              Support URL
              <input v-model="vendorPortalForm.support_url" type="url" maxlength="255" placeholder="https://example.com/support">
            </label>
            <label>
              Contact Email
              <input v-model="vendorPortalForm.contact_email" type="email" maxlength="255" placeholder="support@example.com">
            </label>
          </div>
          <div class="pv-two-col">
            <label>
              Telegram
              <input v-model="vendorPortalForm.contact_telegram" maxlength="120" placeholder="@handle">
            </label>
            <label>
              Signal
              <input v-model="vendorPortalForm.contact_signal" maxlength="120" placeholder="Signal username or number">
            </label>
          </div>
          <div class="pv-two-col">
            <label>
              Discord
              <input v-model="vendorPortalForm.contact_discord" maxlength="120" placeholder="Discord handle">
            </label>
          </div>
          <label>
            Response Policy
            <textarea v-model="vendorPortalForm.response_policy" maxlength="1000" rows="3" placeholder="Expected response times, support hours, and preferred contact channel."></textarea>
          </label>
          <label>
            Public Contact Notes
            <textarea v-model="vendorPortalForm.public_contact_notes" maxlength="1000" rows="3" placeholder="Anything members should know before contacting you."></textarea>
          </label>
          <footer class="pv-form-actions">
            <button class="pv-primary-button" type="submit" :disabled="savingVendorProfile">{{ savingVendorProfile ? 'Saving...' : apiMyVendor ? 'Save Profile' : 'Publish Profile' }}</button>
            <router-link v-if="apiMyVendor?.publishStatus === 'published'" :to="apiMyVendor.href" class="pv-small-button">Preview</router-link>
          </footer>
        </form>

        <article v-if="authStore.isAuthenticated && vendorPortalAccessApproved && apiMyVendor" class="pv-form-card pv-vendor-product-manager">
          <header class="pv-panel-header">
            <div>
              <h2>Product Catalog</h2>
              <p class="pv-muted">Add public product listings with prices. Members must contact you outside this site to purchase.</p>
            </div>
            <span class="pv-count">{{ apiMyVendor.productCount }} listed</span>
          </header>
          <p v-if="vendorProductStatusMessage" class="pv-alert pv-alert--compact">{{ vendorProductStatusMessage }}</p>
          <p v-if="vendorProductFormError" class="pv-alert pv-alert--compact">{{ vendorProductFormError }}</p>
          <form class="pv-product-form" @submit.prevent="saveVendorProduct">
            <div class="pv-two-col">
              <label>
                Product Name *
                <input v-model="vendorProductForm.name" required maxlength="160" placeholder="Retatrutide">
              </label>
              <label>
                Product Slug
                <input v-model="vendorProductForm.slug" maxlength="180" pattern="[a-zA-Z0-9-]+" placeholder="retatrutide">
              </label>
            </div>
            <div class="pv-two-col">
              <label>
                Category
                <input v-model="vendorProductForm.category" maxlength="80" placeholder="Peptide">
              </label>
              <label>
                Strength
                <input v-model="vendorProductForm.strength" maxlength="80" placeholder="10mg">
              </label>
            </div>
            <div class="pv-two-col">
              <label>
                Package Size
                <input v-model="vendorProductForm.package_size" maxlength="80" placeholder="1 vial">
              </label>
              <label>
                Purity / Notes
                <input v-model="vendorProductForm.purity_label" maxlength="80" placeholder=">98%">
              </label>
            </div>
            <label>
              Description
              <textarea v-model="vendorProductForm.description" maxlength="2000" rows="3" placeholder="Short public product description and research-use notes."></textarea>
            </label>
            <div class="pv-two-col">
              <label>
                Price
                <input v-model="vendorProductForm.price" inputmode="decimal" placeholder="85.00">
              </label>
              <label>
                Currency
                <input v-model="vendorProductForm.currency_code" maxlength="3" placeholder="USD">
              </label>
            </div>
            <fieldset class="pv-vendor-fieldset">
              <legend>Variants</legend>
              <p class="pv-muted" style="font-size:12px">When variants exist, each variant has its own price. The main price field becomes optional.</p>
              <div v-if="vendorProductForm.variants.length === 0" class="pv-muted" style="font-size:12px;padding:4px 0">No variants yet.</div>
              <div v-for="(variant, vi) in vendorProductForm.variants" :key="vi" class="pv-two-col" style="align-items:end">
                <label>
                  Label
                  <input v-model="variant.label" maxlength="80" placeholder="10mg">
                </label>
                <label>
                  Price
                  <input v-model="variant.price" inputmode="decimal" placeholder="50.00">
                </label>
                <label>
                  Availability
                  <select v-model="variant.availability">
                    <option value="in_stock">In stock</option>
                    <option value="limited">Limited</option>
                    <option value="out_of_stock">Out of stock</option>
                  </select>
                </label>
                <button type="button" class="pv-small-button pv-small-button--danger" @click="removeVendorProductVariant(vi)">Remove</button>
              </div>
              <button type="button" class="pv-small-button" @click="addVendorProductVariant">+ Add Variant</button>
            </fieldset>
            <div class="pv-two-col">
              <label>
                Availability
                <select v-model="vendorProductForm.availability">
                  <option value="in_stock">In stock</option>
                  <option value="limited">Limited</option>
                  <option value="out_of_stock">Out of stock</option>
                </select>
              </label>
              <label>
                Visibility
                <select v-model="vendorProductForm.status">
                  <option value="published">Published</option>
                  <option value="hidden">Hidden</option>
                </select>
              </label>
            </div>
            <label>
              Tags
              <input v-model="vendorProductForm.tags" placeholder="Comma-separated tags, e.g. GLP-1, Research">
            </label>
            <div class="pv-two-col">
              <label>
                Sort Order
                <input v-model="vendorProductForm.sort_order" inputmode="numeric" placeholder="0">
              </label>
              <div class="pv-upload-control">
                <strong>Product Image</strong>
                <div class="pv-image-upload-row">
                  <span class="pv-product-image-preview">
                    <img v-if="vendorProductImagePreview" :src="vendorProductImagePreview" alt="Product image preview">
                    <PvIcon v-else name="flask" />
                  </span>
                  <span>
                    <small>Upload a product-safe image up to 5 MB.</small>
                    <input ref="vendorProductImageInput" type="file" accept="image/*" class="pv-sr-only" @change="selectVendorProductImage">
                    <button class="pv-small-button" type="button" @click="vendorProductImageInput?.click()"><PvIcon name="upload" /> Choose Image</button>
                  </span>
                </div>
              </div>
            </div>
            <footer class="pv-form-actions">
              <button class="pv-primary-button" type="submit" :disabled="savingVendorProduct">{{ savingVendorProduct ? 'Saving...' : editingVendorProductId ? 'Update Product' : 'Add Product' }}</button>
              <button class="pv-small-button" type="button" @click="resetVendorProductForm">Clear</button>
            </footer>
          </form>
          <div class="pv-product-manage-list">
            <p v-if="vendorProductManageList.length === 0" class="pv-muted">No products listed yet.</p>
            <article v-for="product in vendorProductManageList" :key="product.id ?? product.slug" class="pv-product-manage-row">
              <span class="pv-product-thumb"><img v-if="product.imageUrl" :src="product.imageUrl" :alt="product.name"><PvIcon v-else name="flask" /></span>
              <span>
                <strong>{{ product.name }}</strong>
                <small>{{ product.category || 'Uncategorised' }} · {{ product.variants?.length ? product.variants.length + ' variants' : product.priceLabel || 'No price' }} · {{ product.availabilityLabel }} · {{ product.status }}</small>
              </span>
              <button class="pv-small-button" type="button" @click="editVendorProduct(product)">Edit</button>
              <button class="pv-small-button pv-small-button--danger" type="button" @click="deleteVendorProduct(product)">Delete</button>
            </article>
          </div>
        </article>

        <article v-if="authStore.isAuthenticated && vendorPortalAccessApproved && apiMyVendor" class="pv-form-card pv-vendor-document-manager">
          <header class="pv-panel-header">
            <div>
              <h2>Documents</h2>
              <p class="pv-muted">Upload COAs, quality reports, and other supporting documents. These will be publicly visible once published.</p>
            </div>
            <span class="pv-count">{{ apiMyVendor.documents.length }} uploaded</span>
          </header>
          <p v-if="vendorDocumentStatusMessage" class="pv-alert pv-alert--compact">{{ vendorDocumentStatusMessage }}</p>
          <p v-if="vendorDocumentFormError" class="pv-alert pv-alert--compact">{{ vendorDocumentFormError }}</p>
          <form class="pv-document-form" @submit.prevent="saveVendorDocument">
            <div class="pv-two-col">
              <label>
                Document Title *
                <input v-model="vendorDocumentForm.title" required maxlength="200" placeholder="Certificate of Analysis - Batch 001">
              </label>
              <label>
                Category
                <select v-model="vendorDocumentForm.category">
                  <option value="">Select category</option>
                  <option value="coa">Certificate of Analysis (COA)</option>
                  <option value="quality">Quality Report</option>
                  <option value="other">Other</option>
                </select>
              </label>
            </div>
            <label>
              Description
              <textarea v-model="vendorDocumentForm.description" maxlength="1000" rows="2" placeholder="Brief description of the document..."></textarea>
            </label>
            <label>
              File (PDF or image, max 10 MB) *
              <input ref="vendorDocumentFileInput" type="file" accept=".pdf,image/*" class="pv-sr-only" @change="selectVendorDocumentFile">
              <div class="pv-upload-box" role="button" @click="vendorDocumentFileInput?.click()">
                <PvIcon name="upload" />
                <span>{{ vendorDocumentFilePreview ? vendorDocumentFilePreview : 'Click to choose a file' }}</span>
                <small>PDF, PNG, JPG, WebP up to 10 MB</small>
              </div>
            </label>
            <footer class="pv-form-actions">
              <button class="pv-primary-button" type="submit" :disabled="savingVendorDocument">
                {{ savingVendorDocument ? 'Uploading...' : 'Upload Document' }}
              </button>
              <button class="pv-small-button" type="button" @click="resetVendorDocumentForm">Clear</button>
            </footer>
          </form>
          <div class="pv-doc-manage-list">
            <p v-if="apiMyVendor.documents.length === 0" class="pv-muted">No documents uploaded yet.</p>
            <article v-for="doc in documentManageList" :key="doc.id" class="pv-doc-manage-row">
              <span class="pv-doc-icon">
                <PvIcon :name="doc.fileType === 'image' ? 'image' : 'document'" />
              </span>
              <span>
                <strong>{{ doc.title }}</strong>
                <small>
                  {{ doc.category ? doc.category.toUpperCase() + ' · ' : '' }}
                  {{ doc.fileType.toUpperCase() }}
                  {{ doc.description ? ' · ' + doc.description : '' }}
                </small>
              </span>
              <a :href="doc.url" target="_blank" rel="noreferrer" class="pv-small-button">View</a>
              <button class="pv-small-button pv-small-button--danger" type="button" @click="deleteVendorDocument(doc)">Delete</button>
            </article>
          </div>
        </article>

         <article v-else-if="authStore.isAuthenticated && !vendorPortalAccessApproved" class="pv-panel">
           <h2>Vendor Access Required</h2>
           <p class="pv-muted">Read the <router-link to="/discussions/vendor-application-process" class="pv-purple-link">Vendor Application Process</router-link> first, then follow the steps below.</p>
           <dl class="pv-data-list">
             <div><dt>Account</dt><dd>{{ authStore.user?.username || authStore.user?.name || 'Current user' }}</dd></div>
             <div><dt>Vendor Access</dt><dd>Not approved</dd></div>
             <div v-if="apiMyVendor"><dt>Existing Profile</dt><dd>{{ apiMyVendor.publishStatus }}</dd></div>
           </dl>
            <button
              v-if="!vendorAccessRequested"
              class="pv-primary-button pv-full"
              style="margin-top:12px"
              @click="requestVendorAccess"
            >
              Request Vendor Access
            </button>
            <button
              v-else
              class="pv-primary-button pv-full"
              style="margin-top:12px"
              disabled
            >
              <PvIcon name="check" /> Requested
            </button>
           <a :href="publicTelegramUrl" target="_blank" rel="noreferrer" class="pv-small-button pv-full" style="margin-top:8px"><PvIcon name="send" /> Contact Admin on Telegram</a>
         </article>
      </main>

      <aside class="pv-stack">
        <article class="pv-panel">
          <h2>Vendor Access</h2>
          <dl class="pv-data-list">
            <div><dt>Status</dt><dd>{{ !authStore.isAuthenticated ? 'Sign in required' : vendorPortalAccessApproved ? 'Approved' : 'Not approved' }}</dd></div>
            <div><dt>Profile</dt><dd>{{ !authStore.isAuthenticated ? 'Account required' : apiMyVendor ? apiMyVendor.publishStatus : 'Not created' }}</dd></div>
          </dl>
          <button v-if="authStore.user?.roles?.includes('admin') && !vendorPortalAccessApproved" class="pv-primary-button pv-full" style="margin-top:12px" @click="approveVendorAccess">Approve Vendor Access</button>
        </article>

        <article class="pv-panel">
          <h2>What Vendors Can Control</h2>
          <ul class="pv-check-list">
            <li>Approved vendors can edit public profile text and tags</li>
            <li>Approved vendors can maintain support links and contact channels</li>
            <li>Vendor images, websites, and support links are public</li>
            <li>No sales, checkout, payments, or order handling</li>
          </ul>
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
