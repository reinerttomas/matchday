## Comments

### Comment only what the code cannot say

Write a comment when it states something absent from the code: a business rule the code obeys but does not name, a quirk of ceskyflorbal.cz, a workaround, a boundary where the obvious change breaks something, or a term narrower than it looks. Before writing one, ask whether a competent reader of this code would already know it; if so, drop it. A restatement doubles the maintenance surface and goes stale silently.

### Do not restate or group code

Never narrate the line below the comment. Never use banners to group code (`// Relationships`); a method that needs sections wants splitting instead. Never leave commented-out code, changelog entries or ticket history, which git already holds.

### Type-carrying PHPDoc is out of scope

`@property-read` blocks, relationship generics, array shapes, `@phpstan-type` and `@throws` are required and stay even where they look mechanical, because static analysis reads them. The rule targets the prose in a docblock: `/** Create a new instance. */` above a constructor or `/** Execute the job. */` above `handle()` is the same noise as an inline restatement, and `@param User $user` beside `User $user` adds nothing. Existing boilerplate docblocks go when their code is reworked; don't add new ones.

### Comments in English

Comments follow the language of the code, not of the conversation that produced them.

### Multi-line comments use a /** */ block

A comment that spans more than one line is written as a `/** */` block, never as consecutive `//` lines. Single-line comments stay `//`.
