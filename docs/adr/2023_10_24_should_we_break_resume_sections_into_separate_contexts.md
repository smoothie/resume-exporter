# Should we break resume sections into separate contexts?

* Status: accepted
* Date: 2023-10-24

## Context and Problem Statement

The main question here is what contexts exist in this domain and should we separate a resume into
separate contexts to reduce one large domain model.

Background on this is, a resume contains some data which could be split down to 13 sections (
PersonalInformation, WorkHistory, VolunteerHistory, EducationHistory, AwardHistory,
CertificateHistory, PublicationHistory, ListOfSkills, ListOfLanguages, ListOfInterests,
ListOfReferences, ProjectHistory, MetaInformation).

That's quite a lot for one model only. So before going down this route I'd like to reflect pro's on
con's and see how much of effort it might take to split it now or making the split when it is
needed (later).

## Decision Drivers

* Ease of making a change on the model (considering usages and test cases)
* Ease of extending models with new behaviors
* Readability (is it easy to read, easy to know what is going on)

## Considered Options

### Use JsonResume and Three Contexts: Importing, Maintaining, Exporting

The idea is that we receive a resume, format it to a JsonResume and from there we export/translate
it into something else.

So we got three contexts:

- Importing: Makes sure the config applies to an input (e.g. JSON, XML, API, DB, ...) and generates
  a JSONResume.
- Maintaining: Entering data into the resume, warning about flaky fields and so on.
- Exporting: Makes sure the config applies to the JsonResume. And exports the stuff (e.g. PDF, JSON,
  XML, API, DB, ...).

* Good, because clear responsibilities when splittings concerns into contexts.
* Good, because it is easy to understand what each context does.
* Good, because each context is isolated as JsonResume is the one standard to use.
* Good, because gain flexibility, trust and stability when using a standard/protocol as the tool to
  move data through the application.
* Bad, because a context may have too many responsibilities (importing from an API, has different
  problems to solve then importing from XML).

### Use JsonResume and split the importing context into each resume section

Same approach as the [previous](#use-jsonresume-and-three-contexts-importing-maintaining-exporting)
one, with one difference. Here we split the maintaining context into separate smaller chunks.

So instead of three contexts (importing, maintaining, exporting) we get:

- Importing - Makes sure the config applies to an input (e.g. JSON, XML, API, DB, ...) and generates
  a JSONResume.
- Resume - Resume general information
    - Meta
- Personal - Person and meta related to a resume
    - Interests
    - Languages
    - Skills
- Work - Work related action and data
    - References
- Volunteer - Volunteer related action and data
    - References
- Education - Education related action and data
    - References
- Awards - Award related action and data
    - References
- Certifications - Certificates related action and data
    - References
- Publications - Publication related action and data
    - References
- Projects - Past and current projects
    - References
- Exporting - Makes sure the config applies to the JsonResume. And exports the stuff (e.g. PDF,
  JSON, XML, API, DB, ...).

* Good, because each context can dig deeper into the context related problems, e.g. Work could have
  very work specific actions.
* Good, because clear responsibilities when splittings concerns into contexts.
* Good, because each context is isolated as JsonResume is the one standard to use.
* Good, because gain flexibility, trust and stability when using a standard/protocol as the tool to
  move data through the application.
* Bad, because it is hard to know all actions of the resume at one sight because there are so many
  contexts for manipulating a resume.
* Bad, because there are quite some dependencies to the resume.
* Bad, because there might be relations between the contexts which are not yet known, and adding
  those on this setup later might lead to a higher effort.
    * Example: When adding a new Work we usually used at least one skill, and we might want to add
      that skill on the list as well as do a self assessment on that one.
      Adding something in this setup might involve more effort than the three context only approach.

### No JsonResume and Three Contexts: Importing, Maintaining, Exporting

So let's imagine we would not use JsonResume how would that look like:

- Importing: Makes sure the config applies to an input (e.g. JSON, XML, API, DB, ...)*¹.
- Maintaining: Interpreting the input*¹, entering data into the resume, warning about flaky fields
  and so on.
- Exporting: Interpreting the input*¹, makes sure the config applies to the JsonResume. And exports
  the stuff (e.g. PDF, JSON, XML, API, DB, ...).

*¹: We need to interpret the input at some point, so we either would define a domain model or try to
come up with canonical format ourselves. Either we would need a standard we can trust on. So I
thought it could be a choice to use an existent one.

* Bad, because there is no clear split for the responsibility of interpreting the data, as this
  leads to that every context needs to know how to handle any (JSON, XML, YAML, CSV, ...) input.
* Bad, because high effort for maintaining, exporting, as both need to know how to interpret the
  input.

## Decision Outcome

Chosen
option: "[Use JsonResume and Three Contexts: Importing, Maintaining, Exporting](#use-jsonresume-and-three-contexts-importing-maintaining-exporting)".

It's easy to understand, easy to break into smaller chunks and so it feels like the way to go for
now.

## References

* [EventStorming: Glossary Cheat Sheet by DDD Crew](https://virtualddd.com/learning-ddd/ddd-crew-eventstorming-glossary-cheat-sheet)
* [DDD: Current EventStorm](../yed/2023-10-24-event-storming.graphml)
* [JsonResume](https://jsonresume.org/)
