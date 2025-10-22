# Complete Implementation Guide for Chinese Lunisolar Calendar Conversion

The Chinese lunisolar calendar represents one of humanity's most sophisticated timekeeping systems, continuously refined over 2,000+ years. This guide provides complete technical specifications for implementing accurate Gregorian-to-Chinese date conversion, including all astronomical calculations, month numbering algorithms, and edge case handling required for a production-ready implementation.

## EpochBridge Implementation Notes

- Reference implementation lives under `app/Services/Chinese` with `ChineseLunisolarCalendar` orchestrating the suì construction and `AstronomyCalculator` providing Meeus-based Sun/Moon ephemerides.
- ΔT follows the Espenak–Meeus polynomials for 1900–2100 with fallback quadratic approximation beyond that window.
- New moon times come from Meeus chapter 49 (all periodic corrections) and are converted to Beijing civil days by applying ΔT and the UTC+8 offset before flooring to midnight boundaries.
- Major solar terms are solved via Newton iteration on the apparent solar longitude, using the dìngqì convention so leap months naturally emerge from the first post-solstice lunation lacking a zhōngqì.
- Month sequences are cached per suì and per Chinese year: the first regular month after winter solstice + one (skipping any leap copy) becomes 正月; leap detection uses the “first month with no major term” rule.
- Validation and reverse conversion both derive month lengths from the computed lunations, so conversions remain self-consistent with the astronomical model and cover edge cases such as the 2033 leap month 11 anomaly.

## Critical foundation: The winter solstice anchors everything

**Month 11 always contains Winter Solstice (Dōngzhì, 冬至)** - this single rule anchors the entire calendar system. Chinese New Year follows as the first day of month 1, which is the second regular (non-leap) month after month 11. Understanding this relationship, rather than the oversimplified "second new moon after winter solstice" rule, is essential for handling leap years correctly.

The system uses true astronomical positions (the **dìngqì** method adopted in 1645) rather than mean positions. This creates fascinating edge cases like the 2033 anomaly where a leap month occurs after month 11 - unprecedented since the calendar reform 388 years ago. The algorithm naturally handles such cases without special logic when properly implemented.

## The 24 solar terms: Foundation of month numbering

Solar terms divide the ecliptic into 24 equal 15° segments based on the Sun's apparent geocentric longitude. **Twelve major solar terms (zhōngqì)** occurring at multiples of 30° define lunar month numbers, while twelve minor terms (jiéqì) at odd multiples of 15° mark mid-month points.

### Complete 24 solar terms with astronomical data

| Term | Chinese | Pinyin | English | Longitude | Type | Gregorian Date | Month |
|------|---------|--------|---------|-----------|------|---------------|--------|
| J1 | 立春 | Lìchūn | Beginning of Spring | 315° | Minor | Feb 4 | — |
| **Z1** | **雨水** | **Yǔshuǐ** | **Rain Water** | **330°** | **Major** | **Feb 19** | **1** |
| J2 | 驚蟄 | Jīngzhé | Waking of Insects | 345° | Minor | Mar 6 | — |
| **Z2** | **春分** | **Chūnfēn** | **Spring Equinox** | **0°** | **Major** | **Mar 21** | **2** |
| J3 | 清明 | Qīngmíng | Pure Brightness | 15° | Minor | Apr 5 | — |
| **Z3** | **穀雨** | **Gǔyǔ** | **Grain Rain** | **30°** | **Major** | **Apr 20** | **3** |
| J4 | 立夏 | Lìxià | Beginning of Summer | 45° | Minor | May 6 | — |
| **Z4** | **小滿** | **Xiǎomǎn** | **Grain Full** | **60°** | **Major** | **May 21** | **4** |
| J5 | 芒種 | Mángzhòng | Grain in Ear | 75° | Minor | Jun 6 | — |
| **Z5** | **夏至** | **Xiàzhì** | **Summer Solstice** | **90°** | **Major** | **Jun 22** | **5** |
| J6 | 小暑 | Xiǎoshǔ | Slight Heat | 105° | Minor | Jul 7 | — |
| **Z6** | **大暑** | **Dàshǔ** | **Great Heat** | **120°** | **Major** | **Jul 23** | **6** |
| J7 | 立秋 | Lìqiū | Beginning of Autumn | 135° | Minor | Aug 8 | — |
| **Z7** | **處暑** | **Chǔshǔ** | **Limit of Heat** | **150°** | **Major** | **Aug 23** | **7** |
| J8 | 白露 | Báilù | White Dew | 165° | Minor | Sep 8 | — |
| **Z8** | **秋分** | **Qiūfēn** | **Autumnal Equinox** | **180°** | **Major** | **Sep 23** | **8** |
| J9 | 寒露 | Hánlù | Cold Dew | 195° | Minor | Oct 8 | — |
| **Z9** | **霜降** | **Shuāngjiàng** | **Descent of Frost** | **210°** | **Major** | **Oct 23** | **9** |
| J10 | 立冬 | Lìdōng | Beginning of Winter | 225° | Minor | Nov 7 | — |
| **Z10** | **小雪** | **Xiǎoxuě** | **Slight Snow** | **240°** | **Major** | **Nov 22** | **10** |
| J11 | 大雪 | Dàxuě | Great Snow | 255° | Minor | Dec 7 | — |
| **Z11** | **冬至** | **Dōngzhì** | **Winter Solstice** | **270°** | **Major** | **Dec 22** | **11** |
| J12 | 小寒 | Xiǎohán | Slight Cold | 285° | Minor | Jan 6 | — |
| **Z12** | **大寒** | **Dàhán** | **Great Cold** | **300°** | **Major** | **Jan 20** | **12** |

**Key insight**: With the modern dìngqì system (true solar motion), time between consecutive major solar terms varies from **29.44 to 31.44 days** due to Earth's elliptical orbit. Since lunar months are 29-30 days, rarely a single lunar month can contain TWO major solar terms (creating "fake leap months" elsewhere) or none at all. This impossibility under the pre-1645 píngqì system (mean solar motion with constant 30.44-day spacing) makes the 2033 case particularly significant.

## Sun position calculation: Jean Meeus algorithms

For Chinese calendar purposes requiring 1-second timing precision, **the Sun's longitude must be accurate to ~0.04 arcseconds**. The NREL Solar Position Algorithm (based on truncated VSOP87) provides optimal balance of accuracy (±1 arcsecond) and practicality.

### Simplified method (30 arcsecond accuracy)

Sufficient for applications tolerating 25-minute timing uncertainty:

```
T = (JD - 2451545.0) / 36525

L₀ = 280.46646 + 36000.76983·T + 0.0003032·T²  (mean longitude)
M = 357.52911 + 35999.05029·T - 0.0001537·T²   (mean anomaly)

C = (1.914602 - 0.004817·T - 0.000014·T²)·sin(M)
    + (0.019993 - 0.000101·T)·sin(2M)
    + 0.000290·sin(3M)

L_true = L₀ + C

Ω = 125.04 - 1934.136·T
L_apparent = L_true - 0.00569 - 0.00478·sin(Ω)
```

Reduce L_apparent to 0-360° range. This gives the Sun's ecliptic longitude in degrees.

### High-accuracy VSOP87 method

The **NREL/TP-560-34302** technical report provides complete implementation of the Solar Position Algorithm achieving ±0.0003° accuracy for years -2000 to +6000. The method calculates Earth's heliocentric coordinates from VSOP87 periodic terms:

For each coordinate (L, B, R):
```
L = (L₀ + L₁·JME + L₂·JME² + L₃·JME³ + L₄·JME⁴ + L₅·JME⁵) / 10⁸
```

Where JME = Julian Ephemeris Millennium = (JDE - 2451545.0) / 365250

Each term L₀, L₁, etc. is calculated from periodic series:
```
Lᵢ = Σ Aₙ·cos(Bₙ + Cₙ·JME)
```

**Sample L₀ terms for Earth heliocentric longitude** (64 terms total):
- Term 0: A=175347046, B=0, C=0
- Term 1: A=3341656, B=4.6692568, C=6283.07585
- Term 2: A=34894, B=4.6261, C=12566.1517
- Term 3: A=3497, B=2.7441, C=5753.3849

Complete coefficient tables available in NREL TP-560-34302 and freely available C code at nrel.gov/midc/spa/.

### JPL Horizons for validation

For ultimate accuracy and validation, **JPL Horizons Ephemeris** provides sub-milliarcsecond precision:

**Python implementation**:
```python
from astroquery.jplhorizons import Horizons

obj = Horizons(id='10',          # Sun
               location='500@399',  # Geocentric
               epochs='2024-01-01')
eph = obj.ephemerides()
print(f"Ecliptic Longitude: {eph['ObsEclLon'][0]}°")
```

**Critical time scale conversions**: All ephemeris calculations use Terrestrial Time (TT), which differs from Universal Time (UT) by ΔT. For 2024, ΔT ≈ 69 seconds. Historical ΔT varies significantly and must be looked up from IERS tables. Convert final times to **Beijing Time (UTC+8)** for month boundaries.

## Moon position and new moon calculation

New moons (lunar conjunctions) define the first day of each Chinese lunar month. The moment when the Moon's apparent geocentric ecliptic longitude equals the Sun's must be determined to minute-level accuracy.

### Jean Meeus lunar longitude formulas

From Chapter 47 of *Astronomical Algorithms*:

**Mean elements** (T = Julian centuries from J2000.0):
```
L' = 218.3164477 + 481267.88123421·T - 0.0015786·T² 
     + T³/538841 - T⁴/65194000  (mean longitude)

D = 297.8501921 + 445267.1114034·T - 0.0018819·T² 
    + T³/545868 - T⁴/113065000   (mean elongation)

M = 357.5291092 + 35999.0502909·T - 0.0001536·T² 
    + T⁴/24490000                 (Sun's mean anomaly)

M' = 134.9633964 + 477198.8675055·T + 0.0087414·T² 
     + T³/69699 - T⁴/14712000     (Moon's mean anomaly)

F = 93.2720950 + 483202.0175233·T - 0.0036539·T² 
    - T³/3526000 + T⁴/863310000   (argument of latitude)
```

**Eccentricity correction**:
```
E = 1 - 0.002516·T - 0.0000074·T²
```

The lunar longitude calculation requires summing **59 periodic terms** involving combinations of D, M, M', F. The truncated Meeus version provides ~10 arcsecond accuracy, corresponding to ~20 second timing precision - adequate for calendar purposes.

### Direct new moon calculation (Chapter 49)

Rather than solving for conjunction iteratively, Meeus provides direct formulas:

**Step 1: Calculate lunation number**
```
k = (Year - 2000) × 12.3685
```
Round k to nearest integer for new moon.

**Step 2: Time parameter**
```
T = k / 1325.55
```

**Step 3: Mean phase time (JDE)**
```
JDE = 2451550.09766 + 29.530588861·k + 0.00015437·T² 
      - 0.000000150·T³ + 0.00000000073·T⁴
```

**Step 4: Fundamental arguments**
```
E = 1 - 0.002516·T - 0.0000074·T²
M = 2.5534 + 29.10535670·k - 0.0000014·T² - 0.00000011·T³
M' = 201.5643 + 385.81693528·k + 0.0107582·T² + 0.00001238·T³ - 0.000000058·T⁴
F = 160.7108 + 390.67050284·k - 0.0016118·T² - 0.00000227·T³ + 0.000000011·T⁴
Ω = 124.7746 - 1.56375588·k + 0.0020672·T² + 0.00000215·T³
```

**Step 5: Apply correction terms**
The algorithm includes approximately **25 correction terms** for new moon involving powers of M, M', F, and planetary arguments. Implementation of all terms achieves several-second accuracy for dates from -1999 BCE to +3000 CE.

### Beijing time conversion and day boundaries

**Critical procedure**:
1. Calculate conjunction time in Julian Day Ephemeris (JDE)
2. Get ΔT from IERS tables: UTC_JD = JDE - ΔT/86400
3. Add 8-hour offset: Beijing_JD = UTC_JD + 1/3
4. Extract date: **Day_number = floor(Beijing_JD)**
5. **The month starts on Day_number**

**Near-midnight handling**: When conjunction occurs within 15 minutes of midnight, 1-second precision becomes critical to determine correct day. The official Chinese standard GB/T 33661-2017 requires ~1 second accuracy excluding unpublished future leap seconds.

**Historical consideration**: Before 1929, times were based on Beijing meridian (116°25'E) rather than 120°E standard, creating ~14 minute offset. For dates before 1929, this can shift month boundaries by one day for near-midnight conjunctions.

## Lunar month numbering algorithm: The core challenge

The month numbering rules represent the most intellectually demanding aspect of Chinese calendar implementation. Understanding the **suì** (歲) concept is essential.

### The suì framework

A **suì** is the period from one month 11 to the next month 11 (exclusive of the ending month 11). A suì contains either:
- **12 months** (regular suì) - no leap month in this suì
- **13 months** (leap suì) - one leap month in this suì

Since there are always exactly **12 major solar terms per suì** but sometimes 13 months, in a leap suì at least one month must lack a major solar term.

### Complete month numbering algorithm

```
INPUT: Gregorian year Y
OUTPUT: Chinese calendar months for Chinese year NY (with New Year closest to Jan 1, Y)

1. Calculate winter solstice times:
   WS_Y-1 = Winter Solstice in December of year Y-1
   WS_Y = Winter Solstice in December of year Y

2. Calculate all new moon times from December Y-1 through January Y+1
   Times must be in Beijing Time (UTC+8)

3. Find M11_prev = lunar month containing WS_Y-1
   This is month 11 of Chinese year NY-1
   Start_date = first day of this month (day of new moon)

4. Find M11_curr = lunar month containing WS_Y
   This is month 11 of Chinese year NY
   End_date = first day of this month

5. Count complete lunar months from Start_date to End_date (exclusive)
   IF count == 12:
      → Regular suì (no leap month)
      → Number months sequentially: 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
      → Month 1 begins at second new moon after month 11 start
   
   ELSE IF count == 13:
      → Leap suì (has leap month)
      → Calculate all 12 major solar terms in this suì (Z1 through Z12)
      → For each month after M11_prev:
         * Check if month contains a major solar term
         * FIRST month with NO major solar term = LEAP MONTH
         * Stop search after finding leap month
      → Number 11 regular months: 11, 12, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
      → Leap month receives number of preceding month + "leap" designation
      → Month 1 (first regular month after month 12) = Chinese New Year

6. Month containing major term Zn typically (but not always) = month n
   Exception: When double-zhōngqì months occur
```

### Leap month naming rules

Leap months are named **rùn** (閏) + preceding month number:
- Leap month between month 5 and month 6 = **閏五月** (leap month 5)
- Leap month between month 11 and month 12 = **閏十一月** (leap month 11)

**Position in year**: The leap month is inserted where it naturally occurs based on the no-zhōngqì rule. In most historical periods, leap months occur after months 2-9 (spring through autumn). The 2033 case with leap month after month 11 is unprecedented since 1645.

### The mathematical foundation

**Tropical year**: 365.2422 days
**Synodic month**: 29.5306 days
**Ratio**: 365.2422 ÷ 29.5306 = **12.368 lunar months per solar year**

A 12-month lunar year totals only 354.37 days, falling 11 days short of the solar year. Over three years this accumulates to ~33 days, necessitating a leap month. The historical **Metonic cycle** (7 leap months in 19 years) was abandoned in China by the 6th century in favor of precise astronomical calculation using the no-zhōngqì rule.

## Chinese New Year: Precise definition

The commonly stated "second new moon after Winter Solstice" rule is **usually correct but not always accurate**. The precise definition accounts for leap months.

### Exact rule

**Chinese New Year is the first day of the second regular (non-leap) month after month 11.**

This distinction matters in leap years when a leap month falls between month 11 and month 1. In such cases (like 2033), Chinese New Year is the **third astronomical new moon** after Winter Solstice, not the second.

### Step-by-step determination algorithm

```
1. For Gregorian year Y, calculate Winter Solstice in December Y-1
2. Find the lunar month containing this winter solstice (month 11)
3. Count forward through lunar months
4. Second REGULAR (non-leap) month after month 11 = month 1
5. First day of month 1 = Chinese New Year

Example for 2033:
- Winter Solstice Dec 21, 2033 falls in month 11 (Nov 22 - Dec 21)
- Next month (Dec 22 - Jan 19, 2034) has NO major solar term → leap month 11
- Following month (Jan 20 - Feb 18, 2034) = month 12
- Next month (Feb 19 - Mar 19, 2034) = month 1
- Chinese New Year 2034 = February 19, 2034
```

**Date range**: Chinese New Year always falls between **January 21 and February 20** in the Gregorian calendar. More than 96% of the time, it coincides with the new moon closest to Lìchūn (Beginning of Spring, ~February 4).

## Complete sexagenary cycle system

The 60-year cycle (gānzhī, 干支) provides stem-branch designations for years, months, days, and hours, creating a complete four-pillar timekeeping system used in Chinese astrology and traditional timekeeping.

### Ten Heavenly Stems (天干 Tiāngān)

| # | Chinese | Pinyin | Yin/Yang | Five Element |
|---|---------|--------|----------|--------------|
| 1 | 甲 | Jiǎ | Yang | Yang Wood |
| 2 | 乙 | Yǐ | Yin | Yin Wood |
| 3 | 丙 | Bǐng | Yang | Yang Fire |
| 4 | 丁 | Dīng | Yin | Yin Fire |
| 5 | 戊 | Wù | Yang | Yang Earth |
| 6 | 己 | Jǐ | Yin | Yin Earth |
| 7 | 庚 | Gēng | Yang | Yang Metal |
| 8 | 辛 | Xīn | Yin | Yin Metal |
| 9 | 壬 | Rén | Yang | Yang Water |
| 10 | 癸 | Guǐ | Yin | Yin Water |

### Twelve Earthly Branches (地支 Dìzhī)

| # | Chinese | Pinyin | Zodiac | Yin/Yang | Five Element | Direction |
|---|---------|--------|--------|----------|--------------|-----------|
| 1 | 子 | Zǐ | Rat | Yang | Yang Water | North |
| 2 | 丑 | Chǒu | Ox | Yin | Yin Earth | NNE |
| 3 | 寅 | Yín | Tiger | Yang | Yang Wood | ENE |
| 4 | 卯 | Mǎo | Rabbit | Yin | Yin Wood | East |
| 5 | 辰 | Chén | Dragon | Yang | Yang Earth | ESE |
| 6 | 巳 | Sì | Snake | Yin | Yin Fire | SSE |
| 7 | 午 | Wǔ | Horse | Yang | Yin Fire | South |
| 8 | 未 | Wèi | Goat | Yin | Yin Earth | SSW |
| 9 | 申 | Shēn | Monkey | Yang | Yang Metal | WSW |
| 10 | 酉 | Yǒu | Rooster | Yin | Yin Metal | West |
| 11 | 戌 | Xū | Dog | Yang | Yang Earth | WNW |
| 12 | 亥 | Hài | Pig | Yin | Yin Water | NNW |

### Pairing rules creating 60 combinations

**Yang Heavenly Stems** (Jiǎ, Bǐng, Wù, Gēng, Rén) pair ONLY with **Yang Earthly Branches** (Zǐ, Yín, Chén, Wǔ, Shēn, Xū).

**Yin Heavenly Stems** (Yǐ, Dīng, Jǐ, Xīn, Guǐ) pair ONLY with **Yin Earthly Branches** (Chǒu, Mǎo, Sì, Wèi, Yǒu, Hài).

This yin-yang constraint eliminates half the possible combinations, creating exactly **60 valid pairs** (not 120). The cycle begins with Jiǎ Zǐ (甲子) and ends with Guǐ Hài (癸亥).

### Year stem-branch calculation formulas

**Reference**: 1984 CE = Jiǎ Zǐ (甲子) - beginning of current 60-year cycle

**For years CE/AD**:
```
Stem_number = 1 + mod(Year + 6, 10)
Branch_number = 1 + mod(Year + 8, 12)
```

**Example for 2024**:
```
Stem = 1 + mod(2024 + 6, 10) = 1 + mod(2030, 10) = 1 + 0 = 1 = Jiǎ (甲)
Branch = 1 + mod(2024 + 8, 12) = 1 + mod(2032, 12) = 1 + 4 = 5 = Chén (辰)
Result: 2024 = Jiǎ Chén (甲辰) - Year of the Dragon
```

**Important**: The Chinese year changes at Lunar New Year (late January to mid-February), not January 1. For astrology purposes, some systems use Lìchūn (Beginning of Spring, ~February 4) as the year change point.

### Month stem-branch formula

**Branch of month**: Fixed by lunar month position
- Month 1 (正月) = Yín branch (寅)
- Month 2 (二月) = Mǎo branch (卯)
- Month 11 (十一月) = Zǐ branch (子) - contains Winter Solstice
- Month 12 (十二月) = Chǒu branch (丑)

**Stem of month**: Depends on year stem

| Year Stem | First Month (Yín) Stem |
|-----------|------------------------|
| Jiǎ (甲) or Jǐ (己) | Bǐng (丙) - 3rd stem |
| Yǐ (乙) or Gēng (庚) | Wù (戊) - 5th stem |
| Bǐng (丙) or Xīn (辛) | Gēng (庚) - 7th stem |
| Dīng (丁) or Rén (壬) | Rén (壬) - 9th stem |
| Wù (戊) or Guǐ (癸) | Jiǎ (甲) - 1st stem |

Stems advance sequentially for subsequent months.

### Day stem-branch formula (VERIFIED CORRECT)

**Reference point**: January 27, 2019 at noon = JDN 2,458,511 = Jiǎ Zǐ (甲子)

```
Given Julian Day Number at noon (JDN):
Stem = 1 + mod(JDN - 1, 10)
Branch = 1 + mod(JDN + 1, 12)
```

**Example verification for March 13, 1781**:
```
JDN = 2,371,629
Stem = 1 + mod(2,371,628, 10) = 1 + 8 = 9 = Rén (壬)
Branch = 1 + mod(2,371,630, 12) = 1 + 10 = 11 = Xū (戌)
Result: Rén Xū (壬戌) ✓
```

**Historical continuity**: The sexagenary day count has been **unbroken for over 2,700 years** since the Shang dynasty, making it the longest continuous calendar sequence in human history.

## The 2033 anomaly: Astronomical explanation

In Chinese year N2033 (January 31, 2033 to February 18, 2034), a leap month occurs after month 11 - the first such occurrence since the 1645 Shíxiàn calendar reform implemented true solar motion calculations.

### What happens in 2033

**Timeline**:
- Month 11: November 22, 2033 - December 21, 2033 (contains Winter Solstice Dec 21)
- **Leap month 11**: December 22, 2033 - January 19, 2034 (no major solar term)
- Month 12: January 20, 2034 - February 18, 2034
- Chinese New Year 2034: February 19, 2034

### Why unprecedented since 1645

The 1645 reform fundamentally changed solar term calculation:

**Before 1645 (Píngqì system)**:
- Solar terms based on **mean solar motion**
- Time between major solar terms constant: 365.2422 ÷ 12 = **30.44 days**
- Since lunar months maximum 30 days, **impossible** for two major solar terms in one month
- Leap months equally distributed throughout the year

**After 1645 (Dìngqì system)**:
- Solar terms based on **true solar motion**
- Time between major solar terms varies: **29.44 to 31.44 days** (Kepler's 2nd Law)
- Now possible for two major solar terms in one lunar month
- Creates "fake leap months" and concentrates leap months in summer months
- Winter leap months become extremely rare

### The astronomical cause

In 2033, **two months each contain TWO major solar terms**:

1. **Month 9** (Sep 23 - Oct 22): Contains Z8 (Sep 23) AND Z9 (Oct 23)
2. **Month 10** (Oct 23 - Nov 21): Contains Z9 (Oct 23) AND Z10 (Nov 22)

These "double zhōngqì" months steal major solar terms from surrounding months, creating **three consecutive months without major solar terms**:
- Month 8 (Aug 25 - Sep 22): No major solar term
- Month following month 11 (Dec 22 - Jan 19): No major solar term ← **becomes leap month 11**
- Month 1 of N2034 (Feb 19 - Mar 19): No major solar term (fake leap month, ignored)

**Why winter solstice position matters**: Winter Solstice (Dec 21, 2033) occurs late in month 11 on day 30. The next new moon just one day later (Dec 22) starts a month without a major solar term immediately after month 11, making it the leap month by the first-lacking-zhōngqì rule.

### Algorithmic handling: No special cases needed

The robust algorithm naturally handles 2033 **without special logic**:

```
Suì S2034 (Nov 22, 2033 to Dec 11, 2034):
- Count months: 13 → Leap suì
- Check each month after month 11 for major solar terms:
  * Month starting Nov 22: Contains Z11 (Dec 21) → Month 11 ✓
  * Month starting Dec 22: Contains NO major solar term → LEAP MONTH 11 ✓
  * STOP (even though Jan 20 month also lacks major solar term)
```

**Common implementation errors that caused incorrect pre-1990s predictions**:
1. Using simplified rule "any month without major solar term is leap month" (only true pre-1645)
2. Following approximate 19-year Metonic cycle pattern
3. Not accounting for fake leap months in non-leap suì

Future occurrences: Leap month 11 will recur in 2128, 2147, and 2242. Leap month 1 predicted for 2262 (first since 1645). Leap month 12 not expected until 3358.

## Edge cases and historical variations

### Can a lunar month contain TWO major solar terms?

**Yes** - occurs when two consecutive major solar terms are separated by less than 30 days and a 30-day lunar month spans both. Between 1800-2100, months with two major solar terms occurred in 1832, 1851, 1870, 1984, and **2033 has TWO such months** (extremely rare). Future occurrences: 2128, 2147, 2242.

**Consequences**: "Steals" major solar term from following month, creating fake leap months nearby and occasionally causing month numbering complexities.

### Lunar month straddling Winter Solstice

**Rule**: The month that CONTAINS Winter Solstice is always month 11, regardless of when during the month the solstice occurs. Even if Winter Solstice falls on day 29 or 30 of a lunar month (as in 2033), that month is definitively month 11.

If new moon and Winter Solstice occur on the same day, that day starts month 11 and the solstice is guaranteed to fall within that month.

### New moon at exactly midnight Beijing time

**Official standard (GB/T 33661-2017)**: Times calculated to ~1 second accuracy. If new moon time is 00:00:00, that is the first moment of the new day, so the month starts that day.

**Historical complications**:

**Before 1929**: Times based on Beijing meridian (116°25'E) rather than 120°E standard, creating ~14 minute difference. Example: 1916 Chinese New Year calculated as Feb 4 at 00:05 (UTC+8) but Feb 3 at 23:51 (Beijing meridian time) - actually celebrated on Feb 3.

**1978 Mid-Autumn Festival controversy**: Purple Mountain Observatory calculated month 8 conjunction as Sep 3 at 00:09 (UTC+8), but Imperial Perpetual Calendar showed Sep 2 at 23:56 (Beijing apparent solar time). Different dates in Hong Kong vs. Mainland. After 1978, Hong Kong adopted PMO calculations.

**2013 Dragon Boat Festival**: Month 5 conjunction at June 8, 23:56 (UTC+8) vs. June 9 in some old calendars. This led to the 2017 GB/T 33661-2017 standard to prevent future discrepancies.

### Historical calculation method changes

**Timeline of major reforms**:

**104 BCE - Tàichū Calendar**: Established Winter Solstice in month 11, no-zhōngqì rule for leap months, year begins month 1 (yín month). Used píngshuo + píngqì (mean motion for Moon and Sun).

**619 CE - Tang Dynasty**: Changed píngshuo → díngshuò (true lunar motion using parabolic models). Maintained píngqì for solar terms.

**1645 CE - Shíxiàn Calendar**: Changed píngqì → dìngqì (true solar motion). Western astronomy, sinusoidal functions, Tychonic model. Modified leap month rule: first month without zhōngqì after month 11 in leap suì = leap month. Created possibility of double zhōngqì months and fake leap months.

**1929 CE - Nationalist Government**: Established Rule 1 - times based on 120°E meridian (UTC+8), not Beijing meridian (116°25'E). Previously ~14 minute difference could affect dates near midnight.

**2017 CE - GB/T 33661-2017**: Codified all rules with modern astronomical terminology. 1-second accuracy requirement (excluding future leap seconds). Purple Mountain Observatory designated as official authority.

**For implementation**: Modern algorithms must use true astronomical positions (dìngqì/díngshuò). For historical dates pre-1645, use píngqì calculations; pre-619, use píngshuo. For dates pre-1929, consider Beijing meridian time.

## Validation data and lookup tables

### Specific Chinese New Year dates with full details

| Year | Date | Stem-Branch | New Moon Time (UTC+8) | Day | Zodiac |
|------|------|-------------|----------------------|-----|---------|
| 2000 | Feb 5 | Gēng Chén 庚辰 | 21:05 | Sat | Dragon |
| 2010 | Feb 14 | Gēng Yín 庚寅 | 10:53 | Sun | Tiger |
| 2020 | Jan 25 | Gēng Zǐ 庚子 | 5:45 | Sat | Rat |
| 2024 | Feb 10 | Jiǎ Chén 甲辰 | 7:01 | Sat | Dragon |
| 2025 | Jan 29 | Yǐ Sì 乙巳 | 20:39 | Wed | Snake |
| 2033 | Jan 31 | Guǐ Chǒu 癸丑 | 6:02 | Mon | Ox |

### Hong Kong Observatory conversion tables

**Main access**: https://www.hko.gov.hk/en/gts/time/conversion.htm

**Coverage**: 1901-2100 (200 years)

**Format**: Individual PDF files per year
- Pattern: https://www.hko.gov.hk/en/gts/time/calendar/pdf/files/{YEAR}e.pdf
- Example: https://www.hko.gov.hk/en/gts/time/calendar/pdf/files/2025e.pdf

**API Access**: https://data.weather.gov.hk/weatherAPI/opendata/lunardate.php
- Method: GET with parameter `date` (format YYYY-MM-DD)
- Returns: JSON with lunar year and date
- Valid range: Current year through current year + 2

**Data Portal**: https://data.gov.hk/en-data/dataset/hk-hko-rss-gregorian-lunar-calendar-conversion-table
- Downloadable CSV/JSON formats
- Historical archives and batch downloads

**Accuracy note**: HKO documentation states "uncertainty in calculation of time of moon phases and solar terms decades ahead may be up to a few minutes" for dates far in the future due to unpredictable leap seconds and long-term orbital perturbations.

### Calculate vs. lookup tables trade-offs

**Lookup table approach**:
- **Advantages**: Simplicity, instant O(1) access, pre-validated by authorities, no rounding errors, excellent for testing
- **Disadvantages**: Storage requirements, limited to pre-computed range (typically 1901-2100), requires periodic updates

**Calculation approach**:
- **Advantages**: Unlimited date range, minimal storage, arbitrary precision control, demonstrates astronomical principles
- **Disadvantages**: Complex implementation, accuracy challenges for distant dates (ΔT uncertainty), computationally intensive, validation difficulty

**Recommended hybrid strategy**:
1. Use lookup tables for 1901-2100 (covers 99% of practical use cases)
2. Implement calculations as fallback for dates outside range
3. Cross-validate calculations against lookup tables for verification
4. Cache calculated results for frequently accessed dates
5. Document source for each date returned

## Worked example: Complete conversion of March 15, 2024

### Step 1: Calculate Julian Day Number

March 15, 2024 at noon:
```
JDN = floor(365.25 × (2024 + 4716)) + floor(30.6001 × (3 + 1)) + 15 - 1524.5
JDN = 2,460,385
```

### Step 2: Calculate year stem-branch

```
Stem = 1 + mod(2024 + 6, 10) = 1 + mod(2030, 10) = 1 = Jiǎ (甲)
Branch = 1 + mod(2024 + 8, 12) = 1 + mod(2032, 12) = 5 = Chén (辰)
Year: Jiǎ Chén (甲辰) - Year of Dragon
```

### Step 3: Determine Chinese year boundaries

Chinese New Year 2024 occurred on February 10, 2024. Since March 15 is after this date, we're in Chinese year 2024 (Jiǎ Chén year).

### Step 4: Find which lunar month

From lookup tables or calculation:
- Chinese New Year 2024 (month 1, day 1): February 10, 2024
- Month 2 began: March 10, 2024 (new moon at 17:01 UTC+8)
- Month 3 begins: April 9, 2024

**March 15, 2024 falls in month 2 of Chinese year 2024.**

### Step 5: Calculate day of lunar month

Days from March 10 to March 15 = 5 days
March 15, 2024 = **Month 2, Day 6**

### Step 6: Calculate day stem-branch

```
JDN = 2,460,385
Stem = 1 + mod(2,460,385 - 1, 10) = 1 + mod(2,460,384, 10) = 1 + 4 = 5 = Wù (戊)
Branch = 1 + mod(2,460,385 + 1, 12) = 1 + mod(2,460,386, 12) = 1 + 2 = 3 = Yín (寅)
Day: Wù Yín (戊寅)
```

### Step 7: Calculate month stem-branch

Year stem is Jiǎ (甲), so first month (Yín month) has stem Bǐng (丙).
Month 2 = Mǎo month with stem Dīng (丁).
Month stem-branch: **Dīng Mǎo (丁卯)**

### Step 8: Verify solar term

Check which solar term month 2 should contain:
- Major solar term Z2 (Spring Equinox, 春分) at 0° occurred March 20, 2024
- This confirms month 2 is correctly numbered (contains Z2)

### Complete result

**March 15, 2024 (Gregorian)**
= **Chinese year Jiǎ Chén (甲辰), Month 2, Day 6**
= **Year: Jiǎ Chén (甲辰) - Dragon**
= **Month: Dīng Mǎo (丁卯)**
= **Day: Wù Yín (戊寅)**
= **Full designation: Jiǎ Chén year, Dīng Mǎo month, Wù Yín day** (四柱: Four Pillars used in Chinese astrology)

## Practical implementation recommendations

### Optimal approach: Hybrid calculation + lookup

**For production systems**:

1. **Primary method (1901-2100)**: Use Hong Kong Observatory lookup tables via API or downloaded PDF/CSV data. This covers virtually all practical use cases with authoritative, pre-validated data.

2. **Fallback calculation**: Implement Jean Meeus algorithms (Chapter 47 for Moon, Chapter 49 for new moons, simplified method for Sun or NREL SPA for high accuracy) for dates outside 1901-2100 range.

3. **Validation layer**: Cross-check calculated results against lookup tables within overlapping range. Flag discrepancies for investigation.

4. **Caching strategy**: Store calculated results for frequently accessed dates to amortize computational cost.

5. **Documentation**: Record which source provided each date (HKO lookup, calculated from Meeus, etc.) for debugging and verification.

### Computation complexity considerations

**New moon calculation**: O(1) with ~25 periodic terms from Meeus Chapter 49
**Sun position**: O(1) with ~60 terms for L₀ in VSOP87, or simple 3-term formula for lower accuracy
**Month determination**: O(n) where n = ~13 months per year to check for major solar terms
**Overall**: Linear in number of years converted, with modest constant factors

For batch conversion of many dates, consider:
- Pre-calculate all new moons for year range (12-13 per year)
- Pre-calculate all solar term times for year range (24 per year)
- Store in indexed data structure for rapid lookup during month determination

### Recommended libraries and resources

**Open source implementations**:
- **Python**: Lunisolar package, or interface JPL via astroquery
- **JavaScript**: cosinekitty/astronomy (compact, multi-language)
- **C**: NREL SPA code (public domain, nrel.gov/midc/spa/)
- **C++**: ytliu0.github.io/ElpMpp02 (full ELP/MPP02 implementation)
- **Go**: soniakeys/meeus (complete Meeus algorithms)

**Data sources**:
- Hong Kong Observatory API: Real-time lunar date conversion
- Chinese Fortune Calendar: Historical Chinese New Year dates 1924-2050
- NASA JPL Horizons: Gold standard for ephemeris validation

**Academic references**:
- Jean Meeus: *Astronomical Algorithms* (1998) - Essential formulas
- NREL/TP-560-34302: Complete SPA procedure with tables
- Helmer Aslaksen: "The Mathematics of the Chinese Calendar" - Definitive mathematical treatment
- GB/T 33661-2017: Official Chinese government standard

### Testing strategy

**Essential test cases**:
1. **Standard years**: 2000, 2010, 2020, 2024, 2025 with known Chinese New Year dates
2. **Leap year**: 2023 (leap month 2), 2025 (leap month 6)
3. **2033 anomaly**: Verify leap month 11 handling
4. **Near-midnight conjunctions**: Test dates where new moon within 1 hour of midnight
5. **Historical dates**: Test pre-1929 with Beijing meridian consideration, pre-1645 with píngqì
6. **Boundary dates**: January 21 and February 20 (Chinese New Year range limits)
7. **Solar term alignment**: Verify Winter Solstice always in month 11, other major terms in expected months

**Validation approach**: For each test date, verify against Hong Kong Observatory tables, Academia Sinica converter, or multiple independent online converters. Focus on edge cases and dates with documented historical complexity.

### Error handling and uncertainty

**For dates beyond ~20 years**: Acknowledge inherent uncertainty due to unpredictable future leap seconds and long-term orbital perturbations. Hong Kong Observatory notes "uncertainty up to a few minutes" for distant dates.

**For near-midnight cases**: Flag conjunctions or solar terms within 15 minutes of midnight as potentially ambiguous for distant future dates. Accept that 1-second precision becomes impossible beyond ~50 years.

**For historical dates**: Document which calculation method was used (píngqì vs dìngqì, píngshuo vs díngshuò) and whether Beijing meridian offset was applied. Note that historical calendars may have contained computational errors that were officially used.

## Synthesis: A complete algorithm emerges

Implementing a Chinese lunisolar calendar converter requires integrating astronomical calculations, algorithmic month numbering logic, and careful attention to historical variations. The system's elegance lies in how simple rules (Winter Solstice defines month 11, first month lacking major solar term is leap month) generate complex but deterministic behavior across millennia.

**The 2033 case epitomizes this complexity** - a configuration unseen for 388 years yet handled naturally by proper implementation of the post-1645 dìngqì rules. Understanding that double-zhōngqì months cause compensating fake leap months transforms this from an anomaly requiring special handling into an expected consequence of Earth's elliptical orbit.

**For practical implementation**, the hybrid approach (lookup tables for 1901-2100, calculation for edge cases) provides optimal balance of accuracy, performance, and maintainability. The Hong Kong Observatory provides freely accessible, authoritative data covering virtually all practical use cases, while Jean Meeus formulas enable calculation for any date when needed.

The Chinese lunisolar calendar stands as testimony to centuries of astronomical observation and mathematical refinement, encoding both celestial mechanics and cultural tradition into a sophisticated timekeeping system that remains vital for over a billion people today.
