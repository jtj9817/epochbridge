export type Season = 'spring' | 'summer' | 'autumn' | 'winter'

interface SeasonalBackground {
  season: Season
  variant: number
  path: string
}

const BACKGROUNDS: Record<Season, readonly [string, string]> = {
  spring: [
    '/images/sd/backgrounds/spring-rain.webp',
    '/images/sd/backgrounds/spring-clear.webp',
  ],
  summer: [
    '/images/sd/backgrounds/summer-morning.webp',
    '/images/sd/backgrounds/summer-sunset.webp',
  ],
  autumn: [
    '/images/sd/backgrounds/autumn-clear.webp',
    '/images/sd/backgrounds/autumn-rain.webp',
  ],
  winter: [
    '/images/sd/backgrounds/winter-morning.webp',
    '/images/sd/backgrounds/winter-night.webp',
  ],
}

export function getSeason(date: Date): Season {
  const month = date.getMonth()

  if (month >= 2 && month <= 4) {
    return 'spring'
  }

  if (month >= 5 && month <= 7) {
    return 'summer'
  }

  if (month >= 8 && month <= 10) {
    return 'autumn'
  }

  return 'winter'
}

function getDayOfYear(date: Date): number {
  const currentDay = Date.UTC(
    date.getFullYear(),
    date.getMonth(),
    date.getDate()
  )
  const startOfYear = Date.UTC(date.getFullYear(), 0, 0)

  return Math.floor((currentDay - startOfYear) / 86_400_000)
}

export function getSeasonalBackground(date: Date = new Date()): SeasonalBackground {
  const season = getSeason(date)
  const backgrounds = BACKGROUNDS[season]
  const variant = getDayOfYear(date) % backgrounds.length

  return {
    season,
    variant,
    path: backgrounds[variant],
  }
}

export function applySeasonalBackground(date: Date = new Date()): void {
  const background = getSeasonalBackground(date)
  const root = document.documentElement

  root.dataset.season = background.season
  root.dataset.seasonVariant = String(background.variant + 1)
  root.style.setProperty(
    '--sd-season-background',
    `url("${background.path}")`
  )
}
