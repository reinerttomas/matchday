---
paths:
    - 'app/Services/**'
---

# Services

## app/Services holds integrations and domain computations

`app/Services` holds two kinds of classes:

- **Integrations** with something outside the application — ceskyflorbal.cz — and the infrastructure around them: the client, its parsers and the data objects it returns (`app/Services/{Provider}`, e.g. `Ceskyflorbal/CeskyflorbalClient`).
- **Domain computations**: a class that derives a value and writes nothing to the database. It may read the data it needs. Name it after the role it plays in the domain, never `*Service`: `TeamPagePresenter` and `FixtureListPresenter` shape props for a page, `CalendarWriter` and `ChangeSummaryWriter` produce a document, `FixtureFormatter` formats a value, `ImportProgress` tracks state in the cache.

A class that writes to the database goes to an action (`.ai/rules/actions.md`), whatever it is called. A value derived only from a model's own attributes is an accessor or a method on the model.
