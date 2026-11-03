# A new team's slug includes its category; existing teams keep theirs

The federation gives several of our teams the same name and tells them apart only by competition: the men's and women's teams are both "FBC VHST Kutná Hora". We keep the team's name exactly as the federation writes it, store a category on the team (men, women, juniors, …), and derive a new team's slug, and with it its calendar address, from the name and the category (`fbc-vhst-kutna-hora-zeny`). Teams that existed before the category was introduced keep their slug without it, because changing a slug breaks the calendar address players already subscribe to. So the slugs are not uniform: some carry the category and some don't. The slug is fixed when the team is added, so correcting the category later does not change it either.

## Considered Options

- **Put the category into the team's name** ("FBC VHST Kutná Hora ženy"), slug derived from the name as before: rejected because the name should stay as the federation writes it. The UI shows the category beside the name instead.
- **Add a suffix to a taken slug** (`-2`): rejected because the calendar address would no longer say which team it belongs to.
- **Rewrite existing slugs to include the category**: rejected because it would break existing calendar subscriptions.

## Consequences

- Since names alone are not unique, the calendar name (`Team::calendarName()`) is `<name> (<category>)`, so two subscribed calendars can be told apart. Event titles and change summaries name the team without its category.
- To pick the team from the previous season, the app matches the federation name of the team's latest team season together with the category, not the name alone.
