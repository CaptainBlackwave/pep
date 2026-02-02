import { createRouter, createWebHistory } from 'vue-router'
import GameLayout from '../layouts/GameLayout.vue'
import LoginView from '../views/LoginView.vue'
import RegisterView from '../views/RegisterView.vue'
import HomeView from '../views/HomeView.vue'
import CrimesView from '../views/modules/CrimesView.vue'
import CrimeActionView from '../views/modules/CrimeActionView.vue'
import GymView from '../views/modules/GymView.vue'
import HospitalView from '../views/modules/HospitalView.vue'
import BankView from '../views/modules/BankView.vue'
import TravelView from '../views/modules/TravelView.vue'
import DrugsView from '../views/modules/DrugsView.vue'
import TheftView from '../views/modules/TheftView.vue'
import RacingView from '../views/modules/RacingView.vue'
import JailView from '../views/modules/JailView.vue'
import InventoryView from '../views/modules/InventoryView.vue'
import PropertiesView from '../views/modules/PropertiesView.vue'
import CombatView from '../views/modules/CombatView.vue'
import BountyView from '../views/modules/BountyView.vue'
import DetectiveView from '../views/modules/DetectiveView.vue'
import BulletsView from '../views/modules/BulletsView.vue'
import GangView from '../views/modules/GangView.vue'
import MissionsView from '../views/modules/MissionsView.vue'
import AchievementsView from '../views/modules/AchievementsView.vue'
import LeaderboardsView from '../views/modules/LeaderboardsView.vue'
import ForumView from '../views/modules/ForumView.vue'
import OrganizedCrimeView from '../views/modules/OrganizedCrimeView.vue'
import ShopView from '../views/modules/ShopView.vue'
import TicketsView from '../views/TicketsView.vue'
import ChatView from '../views/modules/ChatView.vue'
import ProfileView from '../views/modules/ProfileView.vue'
import ActivityView from '../views/modules/ActivityView.vue'
import WikiView from '../views/modules/WikiView.vue'
import EmploymentView from '../views/modules/EmploymentView.vue'
import EducationView from '../views/modules/EducationView.vue'
import StocksView from '../views/modules/StocksView.vue'
import CasinoView from '../views/modules/CasinoView.vue'

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
          component: () => import('../views/modules/CityView.vue')
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
          component: () => import('../views/modules/ScavengeView.vue')
        },
        {
          path: 'travel',
          name: 'travel',
          component: TravelView
        },
        {
          path: 'skills',
          name: 'skills',
          component: () => import('../views/modules/SkillsView.vue')
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
          path: 'travel',
          name: 'travel',
          component: TravelView
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
          path: 'combat',
          name: 'combat',
          component: CombatView
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
          path: 'missions',
          name: 'missions',
          component: MissionsView
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
