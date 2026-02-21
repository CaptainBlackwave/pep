/**
 * Game Type Definitions for OpenPBBG
 */

/**
 * Item types
 */
export type ItemType = 'weapon' | 'armor' | 'consumable' | 'material' | 'key' | 'misc'

/**
 * Item rarity
 */
export type ItemRarity = 'common' | 'uncommon' | 'rare' | 'epic' | 'legendary'

/**
 * Game item
 */
export interface GameItem {
  id: number
  name: string
  description?: string
  type: ItemType
  rarity: ItemRarity
  icon?: string
  value: number
  stackable: boolean
  max_stack: number
  effects?: ItemEffect[]
}

/**
 * Item effect
 */
export interface ItemEffect {
  type: string
  value: number
  duration?: number
}

/**
 * Inventory item
 */
export interface InventoryItem {
  id: number
  item_id: number
  item: GameItem
  quantity: number
  equipped?: boolean
  slot?: string
}

/**
 * Crime types
 */
export type CrimeCategory = 'theft' | 'assault' | 'fraud' | 'organized' | 'heist'

/**
 * Crime definition
 */
export interface Crime {
  id: number
  name: string
  description?: string
  category: CrimeCategory
  difficulty: number
  nerve_cost: number
  energy_cost?: number
  cooldown?: number
  rewards: CrimeRewards
  requirements?: CrimeRequirements
}

/**
 * Crime rewards
 */
export interface CrimeRewards {
  cash_min: number
  cash_max: number
  experience: number
  items?: { id: number; chance: number }[]
}

/**
 * Crime requirements
 */
export interface CrimeRequirements {
  level?: number
  strength?: number
  speed?: number
  items?: { id: number; quantity: number }[]
}

/**
 * Crime result
 */
export interface CrimeResult {
  success: boolean
  rewards?: {
    cash: number
    experience: number
    items?: InventoryItem[]
  }
  message: string
  cooldown_ends?: string
}

/**
 * Gym training type
 */
export type TrainingType = 'strength' | 'defense' | 'speed' | 'endurance'

/**
 * Gym training session
 */
export interface TrainingSession {
  type: TrainingType
  energy_cost: number
  base_gain: number
  duration: number
}

/**
 * Training result
 */
export interface TrainingResult {
  success: boolean
  stat_gained: number
  energy_used: number
  message: string
}

/**
 * Travel destination
 */
export interface TravelDestination {
  id: number
  name: string
  description?: string
  image?: string
  travel_time: number
  travel_cost: number
  required_level?: number
}

/**
 * Travel status
 */
export interface TravelStatus {
  is_traveling: boolean
  destination?: TravelDestination
  arrival_time?: string
  remaining_seconds?: number
}

/**
 * Jail status
 */
export interface JailStatus {
  is_jailed: boolean
  reason?: string
  release_time?: string
  remaining_seconds?: number
  bail_amount?: number
}

/**
 * Hospital status
 */
export interface HospitalStatus {
  is_hospitalized: boolean
  reason?: string
  release_time?: string
  remaining_seconds?: number
  heal_cost?: number
}

/**
 * Mission types
 */
export type MissionType = 'daily' | 'weekly' | 'story' | 'special'

/**
 * Mission status
 */
export type MissionStatus = 'available' | 'in_progress' | 'completed' | 'failed'

/**
 * Mission definition
 */
export interface Mission {
  id: number
  name: string
  description?: string
  type: MissionType
  status: MissionStatus
  requirements?: MissionRequirement[]
  objectives: MissionObjective[]
  rewards: MissionRewards
  expires_at?: string
}

/**
 * Mission requirement
 */
export interface MissionRequirement {
  type: string
  value: number | string
}

/**
 * Mission objective
 */
export interface MissionObjective {
  id: number
  description: string
  current: number
  target: number
  completed: boolean
}

/**
 * Mission rewards
 */
export interface MissionRewards {
  cash?: number
  experience?: number
  points?: number
  items?: { id: number; quantity: number }[]
}

/**
 * Achievement definition
 */
export interface Achievement {
  id: number
  name: string
  description: string
  icon?: string
  category: string
  points: number
  unlocked_at?: string
  progress?: number
  max_progress?: number
}

/**
 * Leaderboard entry
 */
export interface LeaderboardEntry {
  rank: number
  player_id: number
  username: string
  avatar?: string
  value: number
  level?: number
  gang?: string
}

/**
 * Leaderboard type
 */
export type LeaderboardType = 'level' | 'cash' | 'strength' | 'kills' | 'respect'

/**
 * Leaderboard response
 */
export interface LeaderboardResponse {
  type: LeaderboardType
  entries: LeaderboardEntry[]
  updated_at: string
}

/**
 * Shop item
 */
export interface ShopItem {
  id: number
  item: GameItem
  price: number
  currency: 'cash' | 'points' | 'diamonds'
  stock?: number
  restock_time?: string
}

/**
 * Market listing
 */
export interface MarketListing {
  id: number
  item: GameItem
  quantity: number
  price: number
  seller_id: number
  seller_name: string
  created_at: string
  expires_at: string
}

/**
 * Gang definition
 */
export interface Gang {
  id: number
  name: string
  tag: string
  description?: string
  leader_id: number
  leader_name: string
  member_count: number
  respect: number
  rank?: number
  created_at: string
}

/**
 * Gang member
 */
export interface GangMember {
  user_id: number
  username: string
  avatar?: string
  rank: string
  joined_at: string
  is_online: boolean
}

/**
 * Combat result
 */
export interface CombatResult {
  attacker_id: number
  defender_id: number
  winner_id: number
  attacker_damage: number
  defender_damage: number
  attacker_health_remaining: number
  defender_health_remaining: number
  rewards?: {
    cash?: number
    experience?: number
    respect?: number
  }
  log: CombatLogEntry[]
}

/**
 * Combat log entry
 */
export interface CombatLogEntry {
  round: number
  attacker_action: string
  attacker_damage: number
  defender_action: string
  defender_damage: number
}

/**
 * Bounty definition
 */
export interface Bounty {
  id: number
  target_id: number
  target_name: string
  target_avatar?: string
  amount: number
  placed_by_id: number
  placed_by_name: string
  reason?: string
  created_at: string
  expires_at?: string
  claimed_at?: string
  claimed_by_id?: number
  claimed_by_name?: string
}

/**
 * Daily reward
 */
export interface DailyReward {
  day: number
  claimed: boolean
  reward: {
    cash?: number
    points?: number
    item?: { id: number; name: string }
  }
}

/**
 * Daily rewards status
 */
export interface DailyRewardsStatus {
  current_streak: number
  max_streak: number
  last_claimed: string | null
  can_claim: boolean
  rewards: DailyReward[]
}
