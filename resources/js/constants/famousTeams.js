export const FAMOUS_TEAM_SLUGS = [
    'real-madrid', 'barcelona', 'manchester-city', 'manchester-united',
    'liverpool', 'chelsea', 'arsenal', 'tottenham',
    'paris-saint-germain', 'bayern-munich', 'borussia-dortmund',
    'juventus', 'ac-milan', 'inter-milan', 'napoli',
    'atletico-madrid', 'ajax', 'benfica', 'porto',
    'river-plate', 'boca-juniors', 'flamengo', 'palmeiras',
    'galatasaray', 'fenerbahce',
]

export function isHotMatch(match) {
    return FAMOUS_TEAM_SLUGS.includes(match?.home_team?.slug) || FAMOUS_TEAM_SLUGS.includes(match?.away_team?.slug)
}
