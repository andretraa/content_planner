import type { Metadata } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "Planning Kundrew",
  description: "Ruang kerja untuk merencanakan, menerbitkan, dan mengukur konten sosial media.",
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="id">
      <body>{children}</body>
    </html>
  );
}
