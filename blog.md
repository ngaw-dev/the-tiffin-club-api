# Blog

- Install Matt Pocock skill
- @setup-matt-pocock-skill
- @grill-with-docs
- @to-tickets
- @implement-ticets
- @implement @ticket-file-name.md
    - use mimir skill for implementation
    - commit code changes at each logical stage
    - should follow mimir skills pattern and update for tests use pest php
    - use laravel composer pint for formatting files
    - update api documentaion if changed
- repeat implmeent for rest of the files

# Issues

- the tests will fail if ran tomrrow, update the static time to get today's date

# Implementaion issues noticied

- make phone number requried for pickup
- optional phone number for delivery
- daily email of orders at cutoff time
- staff confirm orders

# Updates

- qna-4.md
    - make phone or email required for all
    - q3 - settings with individual fields single-row table with typed columns: `delivery_suburbs` (JSON), `order_cutoff_time` (time), etc.
    - q4 - add new fields pick up start time end time
    - q5 - all zones free
    - q6 - knowledge base
    - q7 - based on email or phone
- qna-5.md and qna-6.md
    - q8-11 - recommended option
    - Q15 - add api endpoint
    - all other recommended
- qna-7.md
    - no update rely on seeded db values

# Project based instructions

- @setup-matt-pocock-skills based on the @api.md file and create single page front end application structure
- create docs for api endpoints and save in parent folder
- based on @api.md file create single page home page design, use tanstack query for api
- https://the-tiffin-club-api.ddev.site/api/v1/menu/today this is todays menu

# UI/UX Skill

- For api use tanstack query
- Use env variable to stoore base api endpoint
- file organisations page folder and page components in same folder as page, home page in home folder
