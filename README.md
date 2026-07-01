<p align="center">
<a aria-label="Join the Hydrodactyl community on Discord" href="https://discord.gg/mnTJVSSaKp?utm_source=githubreadme&utm_medium=readme&utm_campaign=OSSLAUNCH&utm_id=OSSLAUNCH"><img alt="" src=".github/banner.png"></a>
</p>

> [!IMPORTANT]
> pyrodactyl-oss is now Hydrodactyl, and hosted under the Blueprint organization. It is affiliated with Blueprint, but is **not compatible with the framework or it's extensions**.


<p align="center">
  <a href="https://github.com/BlueprintFramework/hydrodactyl/actions/workflows/dev-build.yaml">
    <img src="https://github.com/BlueprintFramework/hydrodactyl/actions/workflows/dev-build.yaml/badge.svg" alt="Docker">
  </a>
</p>

<h1 align="center">Hydrodactyl</h1>

> [!NOTE]
> Hydrodactyl does not support Blueprint. This project is intended to be an AIO solution.

> [!NOTE]
> All Issues and PRs should be made in the [BlueprintFramework/hydrodactyl repo](https://github.com/blueprintframework/hydrodactyl).

> [!WARNING]
> Hydrodactyl is under development and pre-release. Some UI elements may appear broken, and there might be some bugs.

> [!NOTE]
> Please read our documentation at [https://hydrodactyl.dev](https://hydrodactyl.dev/docs/hydrodactyl) before installing.

> [!IMPORTANT]
> For Hydrodactyl-specific issues, please use the [Hydrodactyl Discord](https://discord.gg/mnTJVSSaKp?utm_source=githubreadme&utm_medium=readme&utm_campaign=OSSLAUNCH&utm_id=OSSLAUNCH) instead of Pterodactyl or Blueprint support channels.

Hydrodactyl is the Pterodactyl-based game server management panel that focuses on performance enhancements, a reimagined, accessible interface, and top-tier developer experience. Builds faster, compiles smaller: Hydrodactyl is the world's best Pterodactyl.

![Dashboard Image](./.github/server-menu.png)

## Changes from vanilla Pterodactyl

- **Smaller bundle sizes:** Hydrodactyl is built using Vite, and significant design changes mean Hydrodactyl's initial download size is over **[170 times smaller than leading Pterodactyl forks, including Pelican](https://i.imgur.com/tKWLHhR.png)**.
- **Faster build times:** Hydrodactyl completes builds in milliseconds with the power of Turbo. Cold builds with zero cache finish in **under 7 seconds**.
- **Faster loading times:** Hydrodactyl's load times are, on average, **[over 16 times faster](https://i.imgur.com/28XxmMi.png)** than other closed-source Pterodactyl forks and Pelican. Smarter code splitting and chunking means that pages you visit in the panel only load necessary resources on demand. Better caching means that everything is simply _snappy_.
- **More secure:** Hydrodactyl's modern architecture means **most severe and easily exploitable CVEs simply do not exist**. We have also implemented SRI and integrity checks for production builds.
- **More accessible:** Hydrodactyl builds with the latest Web accessibility guidelines in mind. Hydrodactyl is **entirely keyboard-navigable, even context menus**, and screen-readers are easily compatible.
- **More approachable:** Hydrodactyl's friendly, approachable interface means that anyone can confidently run a game server.

![Dashboard Image](https://i.imgur.com/kHHOW6P.jpeg)

## Installing Hydrodactyl

See our [Installation](https://hydrodactyl.dev/docs/hydrodactyl/installation) docs page on how to get started.

> [!NOTE]
> Windows is currently only supported for development purposes.

## Local Development

Hydrodactyl has various effortless ways of starting up a ready-to-use, fully-featured development environment. See our [Local Development](https://hydrodactyl.dev/docs/hydrodactyl/local-development) documentation for more information.

## Star History

<a href="https://star-history.com/#BlueprintFramework/hydrodactyl&Date">
  <picture>
    <source media="(prefers-color-scheme: dark)" srcset="https://api.star-history.com/svg?repos=BlueprintFramework/hydrodactyl&type=Date&theme=dark" />
    <source media="(prefers-color-scheme: light)" srcset="https://api.star-history.com/svg?repos=BlueprintFramework/hydrodactyl&type=Date" />
    <img alt="Star History Chart" src="https://api.star-history.com/svg?repos=BlueprintFramework/hydrodactyl&type=Date" />
  </picture>
</a>

## License

- Pterodactyl® Copyright © 2015 - 2022 Dane Everitt and contributors.
- Pyrodactyl™ Copyright © 2023-2025 Pyro Inc. and contributors.
- Pyrodactyl™ Copyright © 2025-2026 Pyrodactyl-oss and contributors.
- Hydrodactyl Copyright © 2026-present Naterfute, Blueprint Framework and contributors.

Apache-2.0
