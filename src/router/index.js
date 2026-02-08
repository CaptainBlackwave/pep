import { createRouter, createWebHistory } from 'vue-router'
import GameLayout from '../layouts/GameLayout.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import ForgotPasswordView from '../views/ForgotPasswordView.vue'
import ResetPasswordView from '../views/ResetPasswordView.vue'
import HomeView from '../views/HomeView.vue'
import SettingsView from '../views/SettingsView.vue'
import NotificationsView from '../views/NotificationsView.vue'
import CrimesView from '../views/plugins/CrimesView.vue'
import CrimeActionView from '../views/plugins/CrimeActionView.vue'
import GymView from '../views/plugins/GymView.vue'
import HospitalView from '../views/plugins/HospitalView.vue'
import BankView from '../views/plugins/BankView.vue'
import TravelView from '../views/plugins/TravelView.vue'
import DrugsView from '../views/plugins/DrugsView.vue'
import TheftView from '../views/plugins/TheftView.vue'
import RacingView from '../views/plugins/RacingView.vue'
import JailView from '../views/plugins/JailView.vue'
import InventoryView from '../views/plugins/InventoryView.vue'
import PropertiesView from '../views/plugins/PropertiesView.vue'
import CombatView from '../views/plugins/CombatView.vue'
import BountyView from '../views/plugins/BountyView.vue'
import DetectiveView from '../views/plugins/DetectiveView.vue'
import BulletsView from '../views/plugins/BulletsView.vue'
import GangView from '../views/plugins/GangView.vue'
import MissionsView from '../views/plugins/MissionsView.vue'
import AchievementsView from '../views/plugins/AchievementsView.vue'
import LeaderboardsView from '../views/plugins/LeaderboardsView.vue'
import ForumView from '../views/plugins/ForumView.vue'
import OrganizedCrimeView from '../views/plugins/OrganizedCrimeView.vue'
import ShopView from '../views/plugins/ShopView.vue'
import TicketsView from '../views/TicketsView.vue'
import ChatView from '../views/plugins/ChatView.vue'
import ProfileView from '../views/plugins/ProfileView.vue'
import ActivityView from '../views/plugins/ActivityView.vue'
import WikiView from '../views/plugins/WikiView.vue'
import EmploymentView from '../views/plugins/EmploymentView.vue'
import EducationView from '../views/plugins/EducationView.vue'
import StocksView from '../views/plugins/StocksView.vue'
import CasinoView from '../views/plugins/CasinoView.vue'
import ExploreView from '../views/plugins/ExploreView.vue'
import HuntingView from '../views/plugins/HuntingView.vue'
import QuestsView from '../views/plugins/QuestsView.vue'
import AlliancesView from '../views/plugins/AlliancesView.vue'
import EventsView from '../views/plugins/EventsView.vue'
import MarketView from '../views/plugins/MarketView.vue'
import MessagingView from '../views/plugins/MessagingView.vue'
import TournamentView from '../views/plugins/TournamentView.vue'

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: [
    {
      path: '/',
      redirect: '/dashboard'
    },
    {
      path: '/login',
      name: 'login',
      component: LoginView,
      meta: { requiresGuest: true }
    },
    {
      path: '/register',
      name: 'register',
      component: RegisterView,
      meta: { requiresGuest: true }
    },
    {
      path: '/forgot-password',
      name: 'forgot-password',
      component: ForgotPasswordView,
      meta: { requiresGuest: true }
    },
    {
      path: '/reset-password',
      name: 'reset-password',
      component: ResetPasswordView,
      meta: { requiresGuest: true }
    },
    {
      path: '/',
      component: GameLayout,
      meta: { requiresAuth: true },
      children: [
        {
          path: 'dashboard',
          name: 'dashboard',
          component: HomeView
        },
        {
          path: 'home',
          name: 'home',
          component: HomeView
        },
        {
          path: 'city',
          name: 'city',
          component: () => import('../views/plugins/CityView.vue')
        },
        {
          path: 'inventory',
          name: 'inventory',
          component: InventoryView
        },
        {
          path: 'missions',
          name: 'missions',
          component: MissionsView
        },
        {
          path: 'combat',
          name: 'combat',
          component: CombatView
        },
        {
          path: 'scavenge',
          name: 'scavenge',
          component: () => import('../views/plugins/ScavengeView.vue')
        },
        {
          path: 'travel',
          name: 'travel',
          component: TravelView
        },
        {
          path: 'skills',
          name: 'skills',
          component: () => import('../views/plugins/SkillsView.vue')
        },
        {
          path: 'forums',
          name: 'forums',
          component: ForumView
        },
        {
          path: 'profile',
          name: 'profile',
          component: ProfileView
        },
        {
          path: 'gym',
          name: 'gym',
          component: GymView
        },
        {
          path: 'hospital',
          name: 'hospital',
          component: HospitalView
        },
        {
          path: 'bank',
          name: 'bank',
          component: BankView
        },
        {
          path: 'crimes',
          name: 'crimes',
          component: CrimesView
        },
        {
          path: 'crimes/:id',
          name: 'crime-action',
          component: CrimeActionView
        },
        {
          path: 'drugs',
          name: 'drugs',
          component: DrugsView
        },
        {
          path: 'theft',
          name: 'theft',
          component: TheftView
        },
        {
          path: 'racing',
          name: 'racing',
          component: RacingView
        },
        {
          path: 'jail',
          name: 'jail',
          component: JailView
        },
        {
          path: 'properties',
          name: 'properties',
          component: PropertiesView
        },
        {
          path: 'bounty',
          name: 'bounty',
          component: BountyView
        },
        {
          path: 'detective',
          name: 'detective',
          component: DetectiveView
        },
        {
          path: 'bullets',
          name: 'bullets',
          component: BulletsView
        },
        {
          path: 'gang',
          name: 'gang',
          component: GangView
        },
        {
          path: 'achievements',
          name: 'achievements',
          component: AchievementsView
        },
        {
          path: 'leaderboards',
          name: 'leaderboards',
          component: LeaderboardsView
        },
        {
          path: 'tickets',
          name: 'tickets',
          component: TicketsView
        },
        {
          path: 'organized-crime',
          name: 'organized-crime',
          component: OrganizedCrimeView
        },
        {
          path: 'shop',
          name: 'shop',
          component: ShopView
        },
        {
          path: 'chat',
          name: 'chat',
          component: ChatView
        },
        {
          path: 'activity',
          name: 'activity',
          component: ActivityView
        },
        {
          path: 'wiki',
          name: 'wiki',
          component: WikiView
        },
        {
          path: 'employment',
          name: 'employment',
          component: EmploymentView
        },
        {
          path: 'education',
          name: 'education',
          component: EducationView
        },
        {
          path: 'stocks',
          name: 'stocks',
          component: StocksView
        },
        {
          path: 'casino',
          name: 'casino',
          component: CasinoView
        },
        {
          path: 'explore',
          name: 'explore',
          component: ExploreView
        },
        {
          path: 'hunting',
          name: 'hunting',
          component: HuntingView
        },
        {
          path: 'quests',
          name: 'quests',
          component: QuestsView
        },
        {
          path: 'alliances',
          name: 'alliances',
          component: AlliancesView
        },
        {
          path: 'events',
          name: 'events',
          component: EventsView
        },
        {
          path: 'market',
          name: 'market',
          component: MarketView
        },
        {
          path: 'messaging',
          name: 'messaging',
          component: MessagingView
        },
        {
          path: 'tournament',
          name: 'tournament',
          component: TournamentView
        },
        {
          path: 'settings',
          name: 'settings',
          component: SettingsView
        },
        {
          path: 'notifications',
          name: 'notifications',
          component: NotificationsView
        },
        {
          path: 'announcements',
          name: 'announcements',
          component: () => import('../views/AnnouncementsView.vue')
        },
        {
          path: 'daily-rewards',
          name: 'daily-rewards',
          component: () => import('../views/DailyRewardsView.vue')
        }
      ]
    }
  ],
})

// Navigation guards
router.beforeEach((to, from, next) => {
  const token = localStorage.getItem('auth_token')
  const requiresAuth = to.matched.some(record => record.meta.requiresAuth)
  const requiresGuest = to.matched.some(record => record.meta.requiresGuest)

  if (requiresAuth && !token) {
    next('/login')
  } else if (requiresGuest && token) {
    next('/dashboard')
  } else {
    next()
  }
})

export default router
