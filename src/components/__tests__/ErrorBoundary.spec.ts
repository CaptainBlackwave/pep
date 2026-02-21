import { describe, it, expect } from 'vitest'
import { mount } from '@vue/test-utils'
import { createRouter, createWebHistory } from 'vue-router'
import ErrorBoundary from '@/components/ErrorBoundary.vue'

const router = createRouter({
  history: createWebHistory(),
  routes: [
    { path: '/', name: 'home', component: { template: '<div>Home</div>' } }
  ]
})

// Component that throws an error
const ThrowError = {
  template: '<div>Normal content</div>',
  mounted() {
    throw new Error('Test error')
  }
}

describe('ErrorBoundary', () => {
  it('renders slot content when no error', () => {
    const wrapper = mount(ErrorBoundary, {
      global: {
        plugins: [router]
      },
      slots: {
        default: '<div class="content">Normal content</div>'
      }
    })

    expect(wrapper.find('.content').exists()).toBe(true)
    expect(wrapper.find('.error-boundary').exists()).toBe(false)
  })

  it('renders error UI when error is captured', async () => {
    const wrapper = mount(ErrorBoundary, {
      global: {
        plugins: [router]
      },
      slots: {
        default: ThrowError
      }
    })

    // Error should be captured and error UI shown
    expect(wrapper.find('.error-boundary').exists()).toBe(true)
    expect(wrapper.find('.error-title').text()).toBe('Something went wrong')
  })

  it('displays error message', async () => {
    const wrapper = mount(ErrorBoundary, {
      global: {
        plugins: [router]
      },
      slots: {
        default: ThrowError
      }
    })

    expect(wrapper.find('.error-message').text()).toContain('Test error')
  })

  it('has retry button', async () => {
    const wrapper = mount(ErrorBoundary, {
      global: {
        plugins: [router]
      },
      slots: {
        default: ThrowError
      }
    })

    expect(wrapper.find('.retry-btn').exists()).toBe(true)
    expect(wrapper.find('.retry-btn').text()).toBe('Try Again')
  })

  it('has home button', async () => {
    const wrapper = mount(ErrorBoundary, {
      global: {
        plugins: [router]
      },
      slots: {
        default: ThrowError
      }
    })

    expect(wrapper.find('.home-btn').exists()).toBe(true)
    expect(wrapper.find('.home-btn').text()).toBe('Go Home')
  })

  it('resets error state on retry', async () => {
    const wrapper = mount(ErrorBoundary, {
      global: {
        plugins: [router]
      },
      slots: {
        default: ThrowError
      }
    })

    // Error state should be true
    expect(wrapper.find('.error-boundary').exists()).toBe(true)

    // Click retry
    await wrapper.find('.retry-btn').trigger('click')

    // Error state should be reset
    expect((wrapper.vm as unknown as { hasError: boolean }).hasError).toBe(false)
  })

  it('emits error event when error is captured', async () => {
    const wrapper = mount(ErrorBoundary, {
      global: {
        plugins: [router]
      },
      slots: {
        default: ThrowError
      }
    })

    const errorEvents = wrapper.emitted('error')
    expect(errorEvents).toBeTruthy()
    expect(errorEvents?.[0]?.[0]).toBeInstanceOf(Error)
  })

  it('shows error icon', async () => {
    const wrapper = mount(ErrorBoundary, {
      global: {
        plugins: [router]
      },
      slots: {
        default: ThrowError
      }
    })

    expect(wrapper.find('.error-icon').exists()).toBe(true)
    expect(wrapper.find('.error-icon').text()).toBe('!')
  })
})
