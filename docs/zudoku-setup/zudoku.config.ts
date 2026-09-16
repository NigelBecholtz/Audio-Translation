import type { ZudokuConfig } from "zudoku";

const config: ZudokuConfig = {
  site: {
    title: "Audio Translation – Documentation",
    logo: {
      src: { light: "/logo-light.svg", dark: "/logo-dark.svg" },
      alt: "Audio Translation",
      width: 130,
    },
    footer: {
      position: "center",
      copyright: `Audio Translation Documentation · © ${new Date().getFullYear()}`,
    },
  },
  navigation: [
    {
      type: "category",
      label: "Documentation",
      link: "01-overview",
      items: [
        {
          type: "category",
          label: "Getting Started",
          items: [
            "01-overview",
            "02-getting-started",
            "03-environment-configuration",
          ],
        },
        {
          type: "category",
          label: "Architecture & Services",
          items: [
            "04-architecture",
            "05-external-services",
            "06-database",
          ],
        },
        {
          type: "category",
          label: "Operations",
          items: [
            "07-queues-and-jobs",
            "08-deployment",
            "09-troubleshooting",
            "10-google-service-account-setup",
          ],
        },
      ],
    },
  ],
  redirects: [{ from: "/", to: "/01-overview" }],
  docs: {
    files: "/pages/**/*.{md,mdx}",
    defaultOptions: {
      toc: true,
      disablePager: false,
      showLastModified: false,
    },
  },
};

export default config;
