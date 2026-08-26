export const FAMOUS_TEAM_SLUGS = [
    'real-madrid', 'barcelona', 'manchester-city', 'manchester-united',
    'liverpool', 'chelsea', 'arsenal', 'tottenham',
    'paris-saint-germain', 'bayern-munich', 'borussia-dortmund',
    'juventus', 'ac-milan', 'inter-milan', 'napoli',
    'atletico-madrid', 'ajax', 'benfica', 'porto',
    'river-plate', 'boca-juniors', 'flamengo', 'palmeiras',
    'galatasaray', 'fenerbahce',
]

// Trùng tiêu chí Featured Highlights (HomeController::getFeaturedHighlights) - dùng cho badge Hot
const HOT_TEAM_SLUGS = [
    // Clubs
    'real-madrid', 'barcelona', 'manchester-city', 'liverpool', 'manchester-united',
    'arsenal', 'chelsea', 'bayern-munich', 'paris-saint-germain',
    'juventus', 'ac-milan', 'inter-milan', 'napoli', 'borussia-dortmund',
    'atletico-madrid', 'tottenham',
    // National teams
    'argentina', 'france', 'spain', 'germany', 'england', 'brazil', 'portugal',
]

export function isHotMatch(match) {
    return HOT_TEAM_SLUGS.includes(match?.home_team?.slug) || HOT_TEAM_SLUGS.includes(match?.away_team?.slug)
}
