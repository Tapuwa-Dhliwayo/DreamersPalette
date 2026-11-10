<!-- PUBLISHING METADATA

SEO title: Successes and Failures: Bringing My Writing Workflows into DreamersPalette

SEO description: Bringing my Campfire writing experience, worldbuilding tools and AI workflow into DreamersPalette—and discovering that local progress hadn't proved the live experience I wanted.

Suggested tags: DreamersPalette, Writing Tools, Worldbuilding, AI, Software Development, Devlog, Creative Workflow

-->

# Successes and failures: Bringing my workflows into one environment

I wanted to bring the way I write into DreamersPalette.

The writing. The world around it. The conversations I have with AI when I need to think something through.

I already had ways of doing those things. But they happened in different places, and I wanted them to work together.

DreamersPalette was where I wanted that experience to live.

So I started building it.

## Bringing my Campfire experience home

The first part was bringing my Campfire writing experience into my own site.

I had a story to write and a world to keep track of. Characters, events, systems and the connections between them.

Those things mattered to the writing. I wanted to move between the manuscript and the world without feeling as though I was leaving one task to start another.

Campfire gave me a reference for the experience I wanted. Building it into DreamersPalette gave me room to decide how that experience should work for me.

What should I see whilst writing? What should I be able to open beside the story? How should I find the information I needed and get back to the passage I was working on?

Those were questions I could answer by using what we built.

And once the writing had a home, I wanted the rest of the world there too.

## I needed to see the world the way I think about it

Adding the components was only part of the work.

An encyclopedia could hold the information. But how did I want to see it?

A character existed in the story, had relationships with other characters, took part in events and changed through an arc. I wanted to explore those things together.

So we added the worldbuilding tools and worked on their presentation.

Characters. Relationships. Timelines. Arcs. The encyclopedia holding the details around them.

Then I looked at what we had made and asked for changes.

I didn't want every part of the world to become another stack of cards and lists. Sometimes I wanted to follow a connection. Sometimes I wanted to see events in order. Sometimes I wanted to look at how an arc changed across its beats.

The information could be correct and still be difficult to think with.

That meant trying different arrangements, looking at them with actual examples and deciding whether they helped me understand my own world.

This part was encouraging. I could see the environment taking shape around how I worked.

## Then I wanted my AI workflow there too

I already used AI as part of my writing process.

I could bring it a passage, explain what I was trying to do and work through the ideas. Ask questions. Push back on an interpretation. Add the context it was missing.

That conversation was part of how I thought through the work.

Now I wanted it inside the app.

If I was discussing a character, the character was already there. If the conversation depended on a rule of the world, I had somewhere to record that rule. If we worked out something useful, I wanted to bring it back into the world I was building.

I wanted to keep that process close to the writing.

## Connecting the conversation to the story

The next step was connecting that workflow to the encyclopedia and the world.

I wanted to build out elements from the story I was already writing.

A passage might give me something to develop about a character. A discussion might expose a relationship I hadn't properly recorded. An event could need a place in the timeline, or an idea could need to be checked against what I had already established.

I wanted the app to help me work through those connections.

That made the context important. I wasn't starting with an empty world and asking for whatever came back. I had existing work, intentions and details that needed to matter to the conversation.

The appeal was being able to discuss something, develop it and put the useful parts where they belonged.

I could see how that would fit into my writing process.

## I was testing locally, but I hadn't tested the whole workflow

We built it and tested it in the local environment.

I could inspect the screens. Work through the interactions. Ask for changes to the way the components behaved and how the world was presented.

There was progress I could see and use.

But through that work, I wasn't using an API configuration.

That turned out to matter far more than I had understood.

I was judging what I could see locally and reading the agents' descriptions of what they had built. I believed I understood how that would become the experience I wanted in the main environment.

I hadn't established that the complete AI workflow would run there in the way I expected.

The local work gave me confidence. Some of that confidence extended further than what I had actually verified.

## Pushing it live exposed the gap

Then I pushed it live.

And realised the experience I wanted wasn't there in the way I had understood it would be.

I had misread how the agents had set up what they had promised.

That was a disappointing thing to discover after all the work on the environment around it.

I had been thinking about how I would use the finished workflow. Now I had to go back and understand what had actually been built, what it relied on and what was missing from my understanding.

There were successes in the work. I had brought the writing and worldbuilding closer to what I wanted.

But the AI workflow was a major reason for bringing those pieces together. Having the surrounding tools didn't resolve that gap.

## There wasn't an easy solution

I wanted there to be a straightforward fix.

Something I could configure, connect or change so I could carry on with the experience I had imagined.

But there wasn't an easy solution.

I already had OpenRouter, so I tried the API-key route.

That seemed like a practical next step. I had an existing account. Perhaps I could use it to make this work.

That failed too.

Now I had the original disappointment and a failed attempt to get past it.

I still wanted the workflow. I just couldn't treat the next possible connection as though it had already solved the problem.

## I needed to rethink what I was building around

This was the frustrating part of bringing an existing workflow into an app.

I knew the experience I was trying to bring over. I used it already. That made it easy to picture how useful it would be beside my manuscript and world.

But putting the interface in the right place didn't establish how the whole experience would operate.

I needed to understand the connection it depended on. What could run in the main environment? What required a different setup? What would I actually have to configure and maintain to use it?

Those questions needed concrete answers before I could trust another plan.

I also needed to be more precise when reading what the agents said was complete. Complete under what conditions? What had been exercised locally? Had we used the same path I would depend on when I sat down to write on the live site?

I had been involved throughout the build. I still misunderstood something central to the result.

That is difficult to catch when the parts you can see keep improving.

## The work was still there. So was the disappointment

I don't want to pretend the whole effort failed.

Getting my writing experience into DreamersPalette mattered. Building the world tools and finding better ways to visualise my story mattered too.

Those were things I wanted, and I could see why I had spent the time on them.

But I also don't want to turn the failed part into a tidy lesson that makes the disappointment disappear.

I wanted to sit down in one environment and work the way I already worked, with the writing, the world and the AI conversation connected.

I hadn't got there.

The API-key attempt hadn't rescued it. The next step needed a rethinking exercise, and I wasn't yet claiming that I had the answer.

I had brought several parts of my workflow together.

Now I needed to work out what it would take to make the rest of it belong there.

---

<!-- EDITOR NOTES — remove before publishing.

First-person draft following the structure of the Routine blog drafts: publishing metadata, a personal development narrative, concrete successes and failures, and an unresolved ending where the author's account remains unresolved.

The supplied beats are the authority for the deployment outcome and failed OpenRouter attempt. No technical diagnosis has been invented. In particular, this draft does not claim an authentication failure, unsupported model, billing problem, provider outage, subscription/API incompatibility or production configuration error.

The phrase "what I wanted was int the main environment" has been interpreted as "what I wanted wasn't in the main environment," following the surrounding disappointment and rethinking beats. Confirm this interpretation when editing.

Local testing without API configuration is described as a limit on what was verified. The draft does not claim that no AI execution occurred locally or identify which local mechanism was used.

Worldbuilding examples draw on the supplied beats and the visible project context: manuscript writing, encyclopedia, characters, relationships, timelines, arcs and visual iteration. They describe the intended experience without claiming that every AI-to-record action shipped or worked in production.

Useful details to add in your own edit:
- One specific Campfire interaction you wanted to bring into DreamersPalette.
- A visual change that made the world easier for you to understand.
- One real example of a passage or conversation becoming a world element.
- What you expected the AI connection to do, and what you discovered it actually required.
- The exact OpenRouter failure, if you want to discuss it publicly; omit keys, account details and private configuration.
- Where the rethinking led, if you want a later revision to include the outcome.

Possible illustrations:
- The manuscript beside a linked encyclopedia entry.
- A relationship network, character arc or timeline you actually use.
- The intended discussion-to-world workflow, with private or unreleased material removed.

-->
