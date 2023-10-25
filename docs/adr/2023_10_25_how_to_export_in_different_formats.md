# How to export to different formats?

* Status: accepted
* Date: 2023-10-25

## Context and Problem Statement

When splitting the application into those three contexts (Importing, Maintaining and Exporting), how
should we export a resume?

As far as I see it we either have the option to be highly coupled on a domain model defined by the
maintaining context or be highly coupled to the standard (current: JsonResume).

## Decision Drivers

* Ease of making changes on the contexts (isolation)
* Flexibility of adding new ways to import and export
* Maintainability - fewer dependencies, impact of a change, low complexity

## Considered Options

### Using a Canonical Domain Model

At some point (probably in the maintaining context) we must define a domain model which holds the
resume data.

Then we already got the model. So we could use that model to export stuff.

* Good, because it is easy to understand as the model and all properties will be well documented.
* Good, because model can be (re)used.
* Bad, because highly coupling to the maintaining context, may lead to a hard time making changes
  on maintaining and exporting contexts.
* Bad, because no isolation for maintaining nor exporting contexts. An unrelated change on
  maintaining context may affect any exporting methods.

### Using a Standard Schema (JsonResume)

Instead of depending on the domain model of the maintaining context we could use the JSON file as it
is as the one source.

Maybe we could add a generic context which gives us some helping methods for working with the
JsonResume and then just depend on the standard as it is.

* Good, because it is easy to understand as the schema is already documented by JsonResume.
* Good, because less coupling between contexts. A change on the maintaining context does not
  strictly affect the exporting context.
* Good, because contexts are isolated again.
* Good, because generic behaviors when working with the standard are extracted into a generic
  reusable context.
* Bad, because more initial effort, as it introduces a generic context for working with the
  standard.

## Decision Outcome

Chosen option: "[Using a Standard Schema (JsonResume)](#using-a-standard-schema-jsonresume)".

Less coupling, isolated contexts, and it is clear what each property does. So kind a makes sense.

## References

* [EventStorming: Glossary Cheat Sheet by DDD Crew](https://virtualddd.com/learning-ddd/ddd-crew-eventstorming-glossary-cheat-sheet)
* [DDD: Current EventStorm](../yed/2023-10-24-event-storming.graphml)
* [JsonResume: Schema](https://jsonresume.org/schema)
