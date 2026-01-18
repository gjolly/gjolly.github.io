---
date: 2026-01-18T15:30:00Z
title: "The race toward Confidential AI inference"
tags: ["Confidential Computing", "AI"]
showtoc: false
---
For almost half a decade now, I have been working on Confidential Computing at Canonical. This position has given me a front-row seat to the evolution of Confidential Computing technologies and their applications.

One of the most exciting applications is Confidential AI inference, which allows AI models to be hosted and executed in a way that can keep the user's input data confidential, even from the service provider itself.

While Apple is announcing [a partnership with Google](https://blog.google/company-news/inside-google/company-announcements/joint-statement-google-apple/), to base its own models on Google Gemini and while some might see this as a failure, it is worth noting that Apple Intelligence already has a meaningful legacy.

With its initial [Private Cloud Compute announcement](https://security.apple.com/blog/private-cloud-compute/), Apple effectively kicked off the race toward confidential AI inference.

That race is very much ongoing. Confidential AI inference sits at the intersection of large-scale model deployment and Confidential Computing, and it is evolving rapidly. Some would like to see Confidential Computing as a way to achieve data sovereignty, making data location irrelevant.

Several startups are entering this space. [Confer](https://confer.to/blog/2026/01/private-inference/) has recently joined the effort, while [Tinfoil](https://www.ycombinator.com/companies/tinfoil) has already secured Y Combinator backing. Their approaches highlight growing momentum outside the hyperscalers.

I wonder though whether these startups can realistically compete with major cloud providers. The latter may move more slowly, but they already control the infrastructure and are actively expanding their Confidential Computing offerings. With technologies like AMD SEV-SNP and Intel TDX, the trust boundary has moved from the hypervisor down to the hardware silicon itself, meaning customers no longer need to trust the cloud provider's software stack. When the underlying confidential computing capabilities are essentially commoditized by the hyperscalers, what differentiates these startups beyond being a UI layer on top of what the cloud already provides?

It is a fascinating space to watch nonetheless. Today's internet is built on TLS and secure end-to-end encryption. Tomorrow's world may well be built around secure attestable data processing, where you can verify not just who you're talking to, but that your data is being processed exactly as promised.