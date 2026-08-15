Scrum is very good at making work visible.

It gives teams a shared backlog, a planning cadence, a way to discuss priorities, and a mechanism for breaking large projects into smaller pieces. For many organizations, that is genuinely useful.

The problem starts when Scrum stops being a coordination tool and becomes the operating model for engineering itself.

Engineering is not primarily the act of completing well-defined tasks. It is the act of understanding ambiguous problems, making trade-offs, investigating constraints, and deciding what the right solution should be.

That distinction matters more than it may seem.

If a ticket arrives with a clearly defined problem, a proposed solution, known dependencies, detailed acceptance criteria, and a reliable estimate, then a substantial part of the engineering work has already happened.

The obvious question is: where?

## Where does the thinking happen?

In theory, Scrum does not prevent engineers from thinking. Nothing in the framework says engineers should blindly execute tickets.

In practice, however, many Scrum organizations try very hard to make work predictable before it enters a sprint.

Stories are refined. Edge cases are discussed. Acceptance criteria are added. Dependencies are identified. The work is split into smaller pieces. Estimates are debated until the team is comfortable making a commitment.

This creates a subtle pressure: uncertainty becomes something that should be removed before the work starts.

But uncertainty is often the work.

A difficult engineering problem is difficult precisely because we do not yet fully understand it. We may not know where the real constraint is. We may not know which design will survive contact with production. We may discover that the original request is solving the wrong problem.

Trying to eliminate all of that uncertainty during refinement does not eliminate the engineering work. It merely moves it somewhere else.

And often, it moves it into a meeting.

A group of people sits around a ticket and attempts to make it "ready." They discuss implementation approaches, architecture, risks, edge cases, and estimates.

The difficulty is that nobody in the room may have spent enough time actually investigating the problem.

So instead of:

**investigate → understand → design → discuss → implement**

the process becomes:

**discuss → speculate → decompose → estimate → commit → investigate**

This helps explain why refinement meetings can become painfully long.

The problem is not merely that meetings take time. It is that the organization is trying to replace focused investigation with committee speculation.

## What are engineers actually paid for?

This becomes especially important for senior engineers.

A strong engineer is not valuable because they can translate an extremely detailed specification into code faster than someone else.

They are valuable because they can take an unclear problem and make sense of it.

They can explore a system they do not fully understand. They can notice that the apparent problem is not the real one. They can balance reliability, maintainability, cost, performance, and product constraints. They can talk to other teams, challenge assumptions, and revise the plan when reality disagrees with it.

In other words, much of the value is in the thought process.

If an organization decomposes every problem until each engineer receives only a small, fully groomed implementation task, it may be making execution easier while simultaneously removing the part of the job that justified hiring experienced engineers in the first place.

There is nothing wrong with implementation work. Programming is difficult and valuable.

But programming is only part of engineering.

And an organization that treats engineers primarily as processors of pre-defined tickets is leaving a lot of expensive capability unused.

## Small tickets create small ownership

Decomposition also has another cost: ownership.

Suppose a feature requires changes to an API, a database, a frontend, some observability, and a migration.

One engineer changes the API. Another changes the database. Someone else implements the interface. Another engineer adds the monitoring.

Everyone completes their ticket.

Who owns the feature?

The usual answer is that "the team owns it."

Sometimes that works.

But collective ownership can easily become ownership by nobody.

Six months later, when the feature is slow, expensive, difficult to modify, or behaving strangely in production, no individual necessarily feels responsible for understanding the whole thing.

Everyone did their part.

That is very different from giving an engineer or a small group a broader responsibility:

> Build feature A over the next two weeks. Here is the outcome we need, the important constraints, and the deadline. Investigate the problem, propose an approach, talk to whoever you need to, and report progress every few days.

That still has structure.

Management still decides priorities. Technical leads still challenge designs. Senior engineers still provide support. Deadlines still exist. Progress is still reviewed.

The difference is that somebody owns the problem rather than merely owning a ticket.

## Process changes behavior

This matters because people learn very quickly what an organization actually rewards.

Imagine an engineer notices a small but real production issue while working on something else.

They could investigate and fix it immediately.

But the fix is not in the sprint.

It may affect the sprint commitment. It may increase cycle time. It may make their current work appear late. It may require explaining why they worked on something that was not planned.

The process-compliant alternative is obvious: create a ticket.

That ticket enters the backlog. Perhaps it gets discussed in refinement two weeks later. Perhaps it gets prioritized. Perhaps somebody eventually picks it up.

The rational behavior becomes:

**Why fix the problem now when I can file a ticket and remain compliant with the process?**

This is not really about laziness.

It is about incentives.

A process teaches people which behaviors are safe, visible, and rewarded. If initiative creates friction while ticket completion creates measurable progress, people will gradually optimize for ticket completion.

The organization then gets exactly what it asked for.

Engineers stop asking, "What should I improve?"

They start asking, "What am I supposed to work on?"

That is a major loss.

## The metrics become the product

Scrum also produces a large amount of measurable activity.

Velocity. Story points. Sprint completion. Cycle time. Tickets completed. Burndown.

These numbers can be useful.

The problem begins when measurable activity quietly becomes a proxy for engineering performance.

Most organizations will insist that velocity is a team planning tool, not a performance metric.

That distinction is weaker than it sounds.

If a number is displayed, discussed, compared over time, reviewed by management, and repeatedly associated with whether things are "going well," people will respond to it.

Teams become better at producing the number.

Tickets are split differently. Uncertain work is avoided. Exploratory work becomes uncomfortable because it is difficult to estimate. Engineers become less willing to fix unrelated problems because doing so damages predictability.

None of this requires management to explicitly announce that velocity is a KPI.

The metric only needs to become salient.

And the problem is that the things Scrum measures easily are not the things a business ultimately cares about.

A business cares about things such as:

- reliability;
- customer satisfaction;
- retention;
- revenue;
- margins;
- operational cost;
- product quality;
- the ability to evolve the system safely.

Story points are several abstractions away from any of these.

An engineering organization can have excellent sprint metrics while building the wrong thing extremely efficiently.

## Measure outcomes, not activity

A better model starts by moving the unit of responsibility upward.

Instead of asking engineers to maximize throughput, give them objectives that connect to what the business actually needs.

For example:

> Reduce the infrastructure cost per active customer by 20% without reducing reliability.

That is an engineering problem.

The solution might involve architecture, caching, storage, an expensive external dependency, technical debt, or something nobody has identified yet.

The engineer's job is to understand which intervention actually matters.

This is much more valuable than assigning a sequence of tickets describing optimizations somebody else has already decided are necessary.

It also changes how technical debt is discussed.

"Refactor this old system" is difficult to prioritize against visible product work.

"Reduce the operational cost of this service" is an objective.

If technical debt is responsible for the cost, addressing that debt becomes a rational business intervention rather than a vague request for engineering cleanliness.

This is the useful part of ideas such as management by objectives: align people around outcomes that matter, then give them enough autonomy to determine how to achieve them.

That does not mean mechanically tying individual bonuses to revenue or retention. Those outcomes are influenced by too many factors.

It means giving engineers enough business context to understand what success looks like and enough ownership to exercise judgment in pursuing it.

Good management tells engineers what outcome matters and gives them constraints.

It does not need to prescribe every step of the thought process.

## Alignment does not require micromanagement

This is where I think Scrum often solves the wrong coordination problem.

Large organizations obviously need alignment.

People cannot simply choose arbitrary projects and hope everything fits together.

Scrum creates alignment by decomposing work into explicit units and coordinating who completes them.

There is another way.

Align people on objectives, then give them responsibility for meaningful problems.

If the company needs to improve retention, engineers should understand that.

That context changes decisions.

Reliability matters because outages cause churn.

Performance matters because poor experience hurts engagement.

Support tooling matters because unresolved customer problems create frustration.

Technical debt matters when it prevents the organization from improving those things quickly.

It is impossible to encode all of that into acceptance criteria.

Alignment does not come from specifying every action precisely.

It comes from making sure people understand what the organization is trying to achieve.

## AI makes this distinction more important

This matters even more as AI becomes better at software implementation.

The more detailed and well-specified a software task becomes, the more suitable it is for automation.

"Add this field to this endpoint, update these tests, expose it in this component" is increasingly the kind of task an AI system can help implement very effectively.

The scarce part of engineering therefore moves upward.

What should we build?

What is actually causing this problem?

Which constraints matter?

What architecture makes sense?

What trade-offs are acceptable?

How do we know whether the solution worked?

These are judgment problems.

There is an irony here.

Many engineering organizations spend enormous amounts of human time converting ambiguous problems into small, precise, implementation-ready tickets.

Those are exactly the tasks AI is becoming increasingly capable of performing.

As implementation gets cheaper, it makes less sense to organize expensive human engineers around implementation alone.

Give humans the ambiguity.

Give them objectives, context, constraints, and responsibility.

Let them investigate.

Let them make decisions.

Let them use AI to accelerate the implementation.

The organizations that adapt well will not necessarily be those that produce the most code. They will be the ones that make the best technical decisions about what should be built and why.

## The alternative to Scrum is not chaos

None of this means abandoning planning or accountability.

Engineers should have deadlines.

They should communicate progress.

Managers should decide priorities.

Technical leads should challenge weak thinking.

Senior engineers should mentor less experienced engineers.

Teams should review whether an approach is feasible before committing significant resources.

The alternative is not a room full of engineers doing whatever they feel like.

It is **accountability without micromanaging the thought process**.

Kanban-style workflows can support this well when the items flowing through the system represent meaningful outcomes rather than tiny implementation steps.

An engineer might own a problem for one or two weeks, give regular progress updates, ask for help when needed, and ultimately be responsible for the quality of the result.

That creates more responsibility, not less.

It also makes the engineer's contribution visible in a way that completing dozens of unrelated tickets never can.

They can point to something meaningful and say:

**I understood this problem. I drove it forward. I made this part of the product better.**

That sense of impact is not incidental.

It is part of what makes good engineers care.

## Scrum is good at organizing work

And that is ultimately my issue with Scrum.

Scrum can be very effective at organizing implementation.

It can make priorities visible. It can structure communication. It can help teams coordinate a large amount of work.

Those are useful properties.

But engineering is not merely organized implementation.

Engineering is the process of resolving uncertainty.

It requires investigation, judgment, ownership, and responsibility for outcomes.

When organizations force every engineering problem into a perfectly groomed sequence of predictable tasks, they can create the appearance of control while removing precisely the behavior they need most from their engineers.

The question should not be:

**How do we make engineers complete tickets more predictably?**

It should be:

**How do we give engineers enough context, responsibility, and autonomy to solve the problems that actually matter?**

Scrum can help organize the work that follows.

It should not replace the engineering that comes before it.