# 02: Ordering window service

**What to build:** A single server-authoritative ordering-window service that all cutoff logic funnels through. It computes, in Pacific/Auckland, whether ordering is open for today: opens at max(09:00, menu publish time); closes at the per-day Cutoff Time, falling back to a global default when none is set; reports a "closing soon" state in the last 60 minutes. The menu response exposes the window state so clients never compute time themselves.

**Blocked by:** 01 (Today's menu read path).

**Status:** done

- [x] Window opens at max(09:00, publish time) — late-published menus open on publish
- [x] Per-day Cutoff Time with global fallback
- [x] "Closing soon" flag inside the last 60 minutes before cutoff
- [x] All time computed server-side in Pacific/Auckland; client clock never trusted
- [x] Unit tests cover midnight rollover, late publish, and fallback-cutoff edges
