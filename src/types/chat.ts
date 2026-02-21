/**
 * Chat Type Definitions for OpenPBBG
 */

/**
 * Chat channel types
 */
export type ChatChannelType = 'public' | 'private' | 'gang' | 'direct' | 'global'

/**
 * Chat channel interface
 */
export interface ChatChannel {
  id: number
  name: string
  slug: string
  type: ChatChannelType
  unread_count?: number
  last_message?: ChatMessage
  created_at?: string
  updated_at?: string
}

/**
 * Chat message interface
 */
export interface ChatMessage {
  id: number
  channel_id: number
  user_id: number
  username: string
  content: string
  created_at: string
  updated_at?: string
}

/**
 * Chat message for creation
 */
export interface CreateChatMessageRequest {
  content: string
}

/**
 * Chat channel response from API
 */
export interface ChatChannelsResponse {
  channels: ChatChannel[]
}

/**
 * Chat messages response from API
 */
export interface ChatMessagesResponse {
  messages: ChatMessage[]
  channel_id?: number
  has_more?: boolean
}

/**
 * Unread count response from API
 */
export interface ChatUnreadCountResponse {
  count: number
  total: number
}

/**
 * Chat user presence
 */
export interface ChatPresence {
  user_id: number
  username: string
  avatar?: string
  status: 'online' | 'away' | 'offline'
  last_seen?: string
}

/**
 * Chat typing indicator
 */
export interface ChatTypingIndicator {
  channel_id: number
  user_id: number
  username: string
  is_typing: boolean
}

/**
 * Direct message conversation
 */
export interface DirectMessageConversation {
  id: number
  participant: {
    id: number
    username: string
    avatar?: string
  }
  last_message?: ChatMessage
  unread_count: number
  created_at: string
  updated_at: string
}

/**
 * Chat channel creation request
 */
export interface CreateChatChannelRequest {
  name: string
  type: ChatChannelType
  members?: number[]
}

/**
 * Chat channel update request
 */
export interface UpdateChatChannelRequest {
  name?: string
  add_members?: number[]
  remove_members?: number[]
}
