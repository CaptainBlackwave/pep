import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { createPinia, setActivePinia } from 'pinia'
import StatBars from '@/components/layout/StatBars.vue'

// Mock the player store
const mockPlayerStore = {
  energy: 75,
  maxEnergy: 100,
  energyPercent: 75,
  energyTimer: '05:30',
  health: 80,
  maxHealth: 100,
  healthPercent: 80,
  healthTimer: '03:45',
  stamina: 50,
  maxStamina: 100,
  staminaPercent: 50,
  staminaTimer: '02:15',
  nerve: 25,
  maxNerve: 100,
  nervePercent: 25,
  nerveTimer: '08:00'
}

vi.mock('@/stores/player', () => ({
  usePlayerStore: () => mockPlayerStore
}))

describe('StatBars', () => {
  beforeEach(() => {
    setActivePinia(createPinia())
  })

  it('renders all four stat bars', () => {
    const wrapper = mount(StatBars)

    const statBars = wrapper.findAll('.stat-bar')
    expect(statBars).toHaveLength(4)
  })

  it('renders energy stat with correct values', () => {
    const wrapper = mount(StatBars)

    const statBars = wrapper.findAll('.stat-bar')
    expect(statBars[0]?.find('.stat-icon').text()).toBe('⚡')
    expect(statBars[0]?.find('.stat-text').text()).toBe('75/100')
    expect(statBars[0]?.find('.stat-timer').text()).toBe('05:30')
  })

  it('renders health stat with correct values', () => {
    const wrapper = mount(StatBars)

    const statBars = wrapper.findAll('.stat-bar')
    expect(statBars[1]?.find('.stat-icon').text()).toBe('🧬')
    expect(statBars[1]?.find('.stat-text').text()).toBe('80/100')
    expect(statBars[1]?.find('.stat-timer').text()).toBe('03:45')
  })

  it('renders stamina stat with correct values', () => {
    const wrapper = mount(StatBars)

    const statBars = wrapper.findAll('.stat-bar')
    expect(statBars[2]?.find('.stat-icon').text()).toBe('💧')
    expect(statBars[2]?.find('.stat-text').text()).toBe('50/100')
    expect(statBars[2]?.find('.stat-timer').text()).toBe('02:15')
  })

  it('renders nerve stat with correct values', () => {
    const wrapper = mount(StatBars)

    const statBars = wrapper.findAll('.stat-bar')
    expect(statBars[3]?.find('.stat-icon').text()).toBe('❤️')
    expect(statBars[3]?.find('.stat-text').text()).toBe('25/100')
    expect(statBars[3]?.find('.stat-timer').text()).toBe('08:00')
  })

  it('applies correct width to energy fill', () => {
    const wrapper = mount(StatBars)

    const fills = wrapper.findAll('.stat-fill')
    expect(fills[0]?.attributes('style')).toContain('width: 75%')
  })

  it('applies correct width to health fill', () => {
    const wrapper = mount(StatBars)

    const fills = wrapper.findAll('.stat-fill')
    expect(fills[1]?.attributes('style')).toContain('width: 80%')
  })

  it('applies correct width to stamina fill', () => {
    const wrapper = mount(StatBars)

    const fills = wrapper.findAll('.stat-fill')
    expect(fills[2]?.attributes('style')).toContain('width: 50%')
  })

  it('applies correct width to nerve fill', () => {
    const wrapper = mount(StatBars)

    const fills = wrapper.findAll('.stat-fill')
    expect(fills[3]?.attributes('style')).toContain('width: 25%')
  })

  it('has correct fill classes for each stat type', () => {
    const wrapper = mount(StatBars)

    const fills = wrapper.findAll('.stat-fill')
    expect(fills[0]?.classes()).toContain('energy')
    expect(fills[1]?.classes()).toContain('health')
    expect(fills[2]?.classes()).toContain('stamina')
    expect(fills[3]?.classes()).toContain('nerve')
  })

  it('has stat-bars container', () => {
    const wrapper = mount(StatBars)

    expect(wrapper.find('.stat-bars').exists()).toBe(true)
  })
})
