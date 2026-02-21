/**
 * User and Player Type Definitions for OpenPBBG
 */

/**
 * Basic user information
 */
export interface User {
  id: number
  username: string
  name?: string
  email: string
  avatar?: string
  roles?: string[]
  permissions?: string[]
}

/**
 * User credentials for login
 */
export interface LoginCredentials {
  login?: string
  email?: string
  password: string
  remember?: boolean
}

/**
 * Data for user registration
 */
export interface RegisterData {
  username: string
  email: string
  password: string
  password_confirmation: string
}

/**
 * Player rank information
 */
export interface PlayerRank {
  id: number
  name: string
  icon?: string
}

/**
 * Player location information
 */
export interface PlayerLocation {
  id: number
  name: string
}

/**
 * Player gang information
 */
export interface PlayerGang {
  id: number
  name: string
  rank?: string
}

/**
 * Player stats
 */
export interface PlayerStats {
  energy: number
  maxEnergy: number
  health: number
  maxHealth: number
  stamina: number
  maxStamina: number
  nerve: number
  maxNerve: number
  cash: number
  bank: number
  points: number
  diamonds: number
  level: number
  experience: number
  experienceToNextLevel: number
  strength: number
  defense: number
  speed: number
  endurance: number
}

/**
 * Player timers for stat regeneration
 */
export interface PlayerTimers {
  energy: string
  health: string
  stamina: string
  nerve: string
  jail: string | null
  travel: string | null
}

/**
 * Full player data including stats and status
 */
export interface Player {
  id: number
  username: string
  name: string
  email?: string
  avatar?: string
  rank: PlayerRank
  location: PlayerLocation
  gang: PlayerGang | null
  stats: PlayerStats
  timers: PlayerTimers
  isJailed: boolean
  isTraveling: boolean
  roles: string[]
  permissions: string[]
}

/**
 * Player profile (public view)
 */
export interface PlayerProfile {
  id: number
  username: string
  name: string
  avatar?: string
  rank: PlayerRank
  location: PlayerLocation
  gang: PlayerGang | null
  level: number
  isOnline: boolean
  lastActive?: string
}

/**
 * Player stats update payload
 */
export interface PlayerStatsUpdate {
  energy?: number
  maxEnergy?: number
  health?: number
  maxHealth?: number
  stamina?: number
  maxStamina?: number
  nerve?: number
  maxNerve?: number
  cash?: number
  bank?: number
  points?: number
  diamonds?: number
  experience?: number
  level?: number
}

/**
 * User settings
 */
export interface UserSettings {
  id: number
  userId: number
  theme: 'light' | 'dark' | 'system'
  language: string
  notificationsEnabled: boolean
  emailNotifications: boolean
  soundEnabled: boolean
  compactMode: boolean
}

/**
 * User session information
 */
export interface UserSession {
  id: string
  userId: number
  ipAddress: string
  userAgent: string
  createdAt: string
  lastActivity: string
  isActive: boolean
}

/**
 * Role definition
 */
export interface Role {
  id: number
  name: string
  displayName: string
  description?: string
  permissions: Permission[]
}

/**
 * Permission definition
 */
export interface Permission {
  id: number
  name: string
  displayName: string
  description?: string
}
