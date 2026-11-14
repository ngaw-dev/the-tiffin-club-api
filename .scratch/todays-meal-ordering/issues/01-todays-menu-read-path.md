# 01: Today's menu read path

**What to build:** A customer can fetch today's menu: the Main (name, description, price) and Extras (name, price). When the menu for today has been published, the endpoint reports the store as open for browsing; when not yet published, it reports the store as closed with a "menu coming soon" state. Includes a seeded demo menu so the behaviour is demoable.

**Blocked by:** None (can start immediately).

**Status:** ready-for-agent

- [ ] `GET /api/v1/menu/today` returns today's main, extras with prices, and a store-open/coming-soon status
- [ ] Exactly one menu per calendar day is honoured (Pacific/Auckland)
- [ ] Unpublished day returns "coming soon", not an error
- [ ] Acceptance covered by feature tests
