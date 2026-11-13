"use client";

import Image from "next/image";
import Link from "next/link";
import { ChangeEvent, FormEvent, useCallback, useEffect, useMemo, useState, useSyncExternalStore } from "react";

type Page = "reports" | "planner" | "ideas" | "settings";
type RecordItem = Record<string, string | number | null | undefined> & { id: string | number };
type IdeaColumn = "ideas" | "in_progress" | "ready_to_shoot" | "completed";
type ModalState = { type: "report" | "schedule" | "idea"; item?: RecordItem; column?: IdeaColumn; date?: string } | null;
type Collection = "reports" | "schedules" | "ideas";
type ConfirmationState =
  | { type: "save"; collection: Collection; id: string | number; payload: Record<string, FormDataEntryValue>; title: string }
  | { type: "delete"; collection: Collection; item: RecordItem }
  | null;

const navigation: { id: Page; label: string; href: string; hint: string }[] = [
  { id: "reports", label: "Laporan Sosmed", href: "/laporan-sosmed", hint: "Analitik & performa" },
  { id: "planner", label: "Content Planner", href: "/content-planner", hint: "Kalender publikasi" },
  { id: "ideas", label: "Ide Konten", href: "/ide-konten", hint: "Papan inspirasi" },
  { id: "settings", label: "Pengaturan", href: "/pengaturan", hint: "Preferensi workspace" },
];

const platforms = ["Instagram", "TikTok", "Threads", "YouTube"];
const reportsPerPage = 7;
const platformLabels: Record<string, string> = {
  instagram: "Instagram",
  tiktok: "TikTok",
  threads: "Threads",
  youtube: "YouTube",
};
const columns: { id: IdeaColumn; label: string; color: string }[] = [
  { id: "ideas", label: "Ide baru", color: "lavender" },
  { id: "in_progress", label: "Sedang dibuat", color: "blue" },
  { id: "ready_to_shoot", label: "Siap tayang", color: "amber" },
  { id: "completed", label: "Selesai", color: "green" },
];
const emptyReport = {
  title: "",
  description: "",
  platform: "Instagram",
  tag: "Reels",
  followers: "",
  tayangan: "",
  jangkauan: "",
  engagement: "",
  date: new Date().toISOString().slice(0, 10),
  period: "mingguan",
};
const emptySchedule = {
  title: "",
  description: "",
  platform: "Instagram",
  tag: "Reels",
  date: new Date().toISOString().slice(0, 10),
  time: "14:00",
  status: "scheduled",
};
const emptyIdea = { title: "", description: "", platform: "Instagram", category: "", column: "ideas" };

function platformLabel(value: unknown) {
  const normalized = String(value ?? "Instagram").trim().toLowerCase();
  return platformLabels[normalized] ?? normalized.charAt(0).toUpperCase() + normalized.slice(1);
}

function formatNumber(value: unknown) {
  const number = Number.parseFloat(String(value ?? "").replace(/[^\d.-]/g, ""));
  return Number.isFinite(number) ? new Intl.NumberFormat("id-ID", { maximumFractionDigits: 1 }).format(number) : "—";
}

function formatDate(value: unknown, options?: Intl.DateTimeFormatOptions) {
  if (!value) return "Tanpa tanggal";
  const date = new Date(`${String(value).slice(0, 10)}T00:00:00`);
  if (Number.isNaN(date.getTime())) return String(value);
  return new Intl.DateTimeFormat("id-ID", options ?? { day: "numeric", month: "short", year: "numeric" }).format(date);
}

function dateKey(date: Date) {
  const local = new Date(date.getTime() - date.getTimezoneOffset() * 60_000);
  return local.toISOString().slice(0, 10);
}

async function readApiResponse(response: Response, fallback: string): Promise<Record<string, unknown>> {
  const body = await response.text();
  let result: unknown;

  if (body) {
    try {
      result = JSON.parse(body);
    } catch {
      throw new Error(`Server mengirim respons yang tidak valid (HTTP ${response.status}). Periksa Runtime Logs di Vercel.`);
    }
  } else {
    throw new Error(`Server tidak mengirim respons (HTTP ${response.status}). Periksa Runtime Logs di Vercel.`);
  }

  if (!result || typeof result !== "object" || Array.isArray(result)) {
    throw new Error(`Format respons server tidak valid (HTTP ${response.status}). Periksa Runtime Logs di Vercel.`);
  }

  const payload = result as Record<string, unknown>;
  if (!response.ok) {
    throw new Error(typeof payload.message === "string" ? payload.message : `${fallback} (HTTP ${response.status}).`);
  }

  return payload;
}

function platformClass(value: unknown) {
  return `platform platform-${String(value ?? "instagram").toLowerCase()}`;
}

function subscribeToSettings(callback: () => void) {
  const listener = (event: StorageEvent) => {
    if (event.key === "planning-kundrew-settings" || event.key === null) callback();
  };
  window.addEventListener("storage", listener);
  return () => window.removeEventListener("storage", listener);
}

function getSettingsSnapshot() {
  return window.localStorage.getItem("planning-kundrew-settings") ?? "{}";
}

function parseSettings(snapshot: string): Record<string, string> {
  try {
    const saved: unknown = JSON.parse(snapshot);
    if (saved && typeof saved === "object" && !Array.isArray(saved)) {
      return Object.fromEntries(Object.entries(saved).filter((entry): entry is [string, string] => typeof entry[1] === "string"));
    }
  } catch {
    return {};
  }
  return {};
}

function getProfileImage(value?: string) {
  return value?.startsWith("data:image/jpeg;base64,") ? value : "";
}

function resizeProfileImage(file: File): Promise<string> {
  if (!["image/jpeg", "image/png", "image/webp", "image/gif"].includes(file.type)) {
    return Promise.reject(new Error("Gunakan foto berformat JPEG, PNG, WebP, atau GIF."));
  }
  if (file.size > 10 * 1024 * 1024) return Promise.reject(new Error("Ukuran foto maksimal 10 MB."));

  return new Promise((resolve, reject) => {
    const objectUrl = URL.createObjectURL(file);
    const image = new window.Image();
    image.onload = () => {
      try {
        const scale = Math.min(1, 512 / Math.max(image.naturalWidth, image.naturalHeight));
        const canvas = document.createElement("canvas");
        canvas.width = Math.max(1, Math.round(image.naturalWidth * scale));
        canvas.height = Math.max(1, Math.round(image.naturalHeight * scale));
        const context = canvas.getContext("2d");
        if (!context) throw new Error("Foto tidak dapat diproses di browser ini.");
        context.drawImage(image, 0, 0, canvas.width, canvas.height);
        const dataUrl = canvas.toDataURL("image/jpeg", 0.82);
        if (dataUrl.length > 1_500_000) throw new Error("Ukuran foto terlalu besar setelah diproses. Pilih foto lain.");
        resolve(dataUrl);
      } catch (error) {
        reject(error instanceof Error ? error : new Error("Foto tidak dapat diproses."));
      } finally {
        URL.revokeObjectURL(objectUrl);
      }
    };
    image.onerror = () => {
      URL.revokeObjectURL(objectUrl);
      reject(new Error("Foto tidak dapat dibaca. Pilih file gambar lain."));
    };
    image.src = objectUrl;
  });
}

function WorkspaceAvatar({ image, initial, className }: { image: string; initial: string; className: string }) {
  return (
    <span className={className}>
      {image ? <Image className="avatar-photo" src={image} alt="" width={48} height={48} unoptimized /> : initial}
    </span>
  );
}

function PlatformIcon({ platform }: { platform: string }) {
  const name = platform.toLowerCase();
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" className="platform-icon">
      {name === "instagram" && (
        <>
          <rect x="3.5" y="3.5" width="17" height="17" rx="5" />
          <circle cx="12" cy="12" r="4" />
          <circle cx="17.5" cy="6.8" r=".8" className="platform-icon-dot" />
        </>
      )}
      {name === "tiktok" && (
        <>
          <path d="M14.4 4v10.1a4.2 4.2 0 1 1-3.5-4.1" />
          <path d="M14.4 4c.5 2.5 2.1 4 4.6 4.4" />
        </>
      )}
      {name === "threads" && (
        <>
          <path d="M17.3 8.6c-.8-2.2-2.5-3.4-5.2-3.4-3.7 0-5.9 2.5-5.9 6.8 0 4.2 2.1 6.9 5.7 6.9 3.2 0 5.1-1.7 5.1-4.4 0-2.3-1.4-3.7-3.8-3.7-1.8 0-3 .8-3.4 2.2" />
          <path d="M11 12.6c4.5-1.2 7.4.3 7.4 3.2 0 1.9-1.4 3.1-3.6 3.1" />
        </>
      )}
      {name === "youtube" && (
        <>
          <rect x="3" y="5.5" width="18" height="13" rx="3.5" />
          <path d="m10 9 5.5 3-5.5 3Z" className="platform-icon-play" />
        </>
      )}
    </svg>
  );
}

function Icon({ name }: { name: string }) {
  const paths: Record<string, string> = {
    spark: "M12 3l1.5 5.5L19 10l-5.5 1.5L12 17l-1.5-5.5L5 10l5.5-1.5L12 3Z M19 15l.8 2.2L22 18l-2.2.8L19 21l-.8-2.2L16 18l2.2-.8L19 15Z",
    plus: "M12 5v14M5 12h14",
    arrow: "M5 12h14m-6-6 6 6-6 6",
    calendar: "M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z",
    chart: "M4 19V5m0 14h16M8 15v-4m4 4V7m4 8v-6",
    search: "m20 20-4.2-4.2M18 10.5a7.5 7.5 0 1 1-15 0 7.5 7.5 0 0 1 15 0Z",
    download: "M12 3v12m0 0 4-4m-4 4-4-4M5 18v3h14v-3",
    edit: "m15 5 4 4M4 20l4-.8L19 8a2.1 2.1 0 0 0-3-3L5 16l-1 4Z",
    trash: "M4 7h16M10 11v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3",
    close: "m6 6 12 12M18 6 6 18",
    clock: "M12 8v4l2.5 1.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z",
    reports: "M4 19V5m0 14h16M8 15v-4m4 4V7m4 8v-6",
    planner: "M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13H4V6a1 1 0 0 1 1-1Z",
    ideas: "M9 18h6m-5 3h4m-5.3-6.1A6 6 0 1 1 15.3 14.9c-.8.6-1.3 1.4-1.3 2.1h-4c0-.7-.5-1.5-1.3-2.1Z",
    settings: "M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm0-5v2m0 13v2m9-8h-2m-14 0H3m15.36-6.36-1.42 1.42M7.06 16.94l-1.42 1.42m12.72 0-1.42-1.42M7.06 7.06 5.64 5.64",
    layers: "m12 3 9 5-9 5-9-5 9-5ZM3 12l9 5 9-5M3 16l9 5 9-5",
    users: "M16 20v-1.5a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4V20M9.5 11a3.5 3.5 0 1 0 0-7 3.5 3.5 0 0 0 0 7Zm7.5-6.8a3.5 3.5 0 0 1 0 6.6M17 14.5h.5a4 4 0 0 1 4 4V20",
    eye: "M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Zm9.5-3.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Z",
    heart: "M20.8 8.8c0 5.2-8.8 10.1-8.8 10.1S3.2 14 3.2 8.8A4.3 4.3 0 0 1 12 6.9a4.3 4.3 0 0 1 8.8 1.9Z",
  };
  return (
    <svg viewBox="0 0 24 24" aria-hidden="true" className="icon">
      <path d={paths[name] ?? paths.spark} />
    </svg>
  );
}

export default function Workspace({ page }: { page: Page }) {
  const [reports, setReports] = useState<RecordItem[]>([]);
  const [schedules, setSchedules] = useState<RecordItem[]>([]);
  const [ideas, setIdeas] = useState<RecordItem[]>([]);
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [modal, setModal] = useState<ModalState>(null);
  const [confirmation, setConfirmation] = useState<ConfirmationState>(null);
  const [query, setQuery] = useState("");
  const [platformFilter, setPlatformFilter] = useState("Semua platform");
  const [period, setPeriod] = useState("mingguan");
  const [calendarView, setCalendarView] = useState<"month" | "week" | "list">("month");
  const [calendarDate, setCalendarDate] = useState(() => new Date());
  const settingsSnapshot = useSyncExternalStore(subscribeToSettings, getSettingsSnapshot, () => "{}");
  const settings = useMemo(() => parseSettings(settingsSnapshot), [settingsSnapshot]);
  const workspaceName = settings.workspaceName?.trim() || "Kundrew Studio";
  const workspaceInitial = workspaceName.charAt(0).toUpperCase() || "K";
  const profileImage = getProfileImage(settings.profileImage);

  const loadData = useCallback(async () => {
    try {
      const responses = await Promise.all(["reports", "schedules", "ideas"].map((item) => fetch(`/api/${item}`, { cache: "no-store" })));
      const payloads = await Promise.all(
        responses.map(async (response) => {
          const json = await readApiResponse(response, "Data tidak dapat dimuat.");
          if (!Array.isArray(json.data)) throw new Error("Format data dari server tidak valid.");
          return json.data as RecordItem[];
        }),
      );
      setReports(payloads[0]);
      setSchedules(payloads[1]);
      setIdeas(payloads[2]);
    } catch (loadError) {
      setError(loadError instanceof Error ? loadError.message : "Data tidak dapat dimuat.");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    void Promise.resolve().then(loadData);
  }, [loadData]);

  useEffect(() => {
    if (!notice) return;
    const timeout = window.setTimeout(() => setNotice(""), 3200);
    return () => window.clearTimeout(timeout);
  }, [notice]);

  const callApi = async (url: string, method: string, body?: unknown) => {
    const response = await fetch(url, {
      method,
      ...(body === undefined ? {} : { headers: { "Content-Type": "application/json" }, body: JSON.stringify(body) }),
    });
    const result = await readApiResponse(response, "Permintaan gagal.");
    return result;
  };

  const addScheduleToReports = async (scheduleId: string | number) => {
    await callApi(`/api/reports/from-schedule/${scheduleId}`, "POST", {});
  };

  const exportCsv = () => {
    void runAction(async () => {
      const params = new URLSearchParams();
      if (platformFilter !== "Semua platform") params.set("platform", platformFilter);
      const queryString = params.toString();
      const response = await fetch(`/api/reports/export${queryString ? `?${queryString}` : ""}`);
      if (!response.ok) {
        await readApiResponse(response, "Laporan tidak dapat diekspor.");
      }
      const blob = await response.blob();
      const objectUrl = URL.createObjectURL(blob);
      const link = document.createElement("a");
      link.href = objectUrl;
      const platformSuffix = platformFilter === "Semua platform" ? "" : `-${platformFilter.toLowerCase()}`;
      link.download = `laporan-sosmed${platformSuffix}-${new Date().toISOString().slice(0, 10)}.csv`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.setTimeout(() => URL.revokeObjectURL(objectUrl), 1000);
    });
  };

  const runAction = async (action: () => Promise<void>) => {
    setBusy(true);
    setError("");
    try {
      await action();
    } catch (actionError) {
      setError(actionError instanceof Error ? actionError.message : "Terjadi kesalahan. Silakan coba lagi.");
    } finally {
      setBusy(false);
    }
  };

  const saveRecord = async (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    if (!modal) return;
    const form = new FormData(event.currentTarget);
    const fields = Object.fromEntries(form.entries());
    const collection = modal.type === "report" ? "reports" : modal.type === "schedule" ? "schedules" : "ideas";
    const payload = {
      ...fields,
      ...(modal.type === "idea" ? { column: fields.column || modal.column || "ideas" } : {}),
    };

    if (modal.item) {
      setConfirmation({ type: "save", collection, id: modal.item.id, payload, title: String(modal.item.title ?? "") });
      return;
    }

    await runAction(async () => {
      const result = await callApi(`/api/${collection}`, "POST", payload);
      if (collection === "schedules" && fields.status === "published") {
        const created = result.data;
        if (!created || typeof created !== "object" || Array.isArray(created) || !("id" in created)) {
          throw new Error("Jadwal berhasil dibuat, tetapi data laporan tidak dapat disiapkan.");
        }
        const scheduleId = created.id;
        if (typeof scheduleId !== "string" && typeof scheduleId !== "number") {
          throw new Error("Jadwal berhasil dibuat, tetapi ID jadwal tidak valid untuk laporan.");
        }
        await addScheduleToReports(scheduleId);
      }
      await loadData();
      setModal(null);
      setNotice("Perubahan berhasil disimpan.");
    });
  };

  const removeRecord = (collection: Collection, item: RecordItem) => {
    setConfirmation({ type: "delete", collection, item });
  };

  const confirmAction = async () => {
    if (!confirmation) return;
    const action = confirmation;
    await runAction(async () => {
      if (action.type === "delete") {
        await callApi(`/api/${action.collection}/${action.item.id}`, "DELETE");
        await loadData();
        setNotice("Data berhasil dihapus.");
        return;
      }

      await callApi(`/api/${action.collection}/${action.id}`, "PUT", action.payload);
      if (action.collection === "schedules" && action.payload.status === "published") {
        await addScheduleToReports(action.id);
      }
      await loadData();
      setModal(null);
      setNotice("Perubahan berhasil disimpan.");
    });
    setConfirmation(null);
  };

  const publishSchedule = (schedule: RecordItem) => {
    void runAction(async () => {
      await addScheduleToReports(schedule.id);
      await callApi(`/api/schedules/${schedule.id}`, "PUT", { ...schedule, status: "published" });
      await loadData();
      setNotice("Jadwal diterbitkan dan otomatis ditambahkan ke laporan.");
    });
  };

  const syncSchedule = (schedule: RecordItem) => {
    void runAction(async () => {
      await addScheduleToReports(schedule.id);
      await loadData();
      setNotice("Jadwal berhasil disinkronkan ke laporan.");
    });
  };

  const moveIdea = (idea: RecordItem, column: IdeaColumn) => {
    void runAction(async () => {
      await callApi(`/api/ideas/${idea.id}`, "PUT", { ...idea, column });
      await loadData();
    });
  };

  const sendIdeaToPlanner = (idea: RecordItem) => {
    void runAction(async () => {
      await callApi(`/api/ideas/${idea.id}/send-to-planner`, "POST", {});
      await loadData();
      setNotice("Ide berhasil ditambahkan ke Content Planner.");
    });
  };

  const syncIdea = (idea: RecordItem) => {
    void runAction(async () => {
      await callApi(`/api/reports/from-idea/${idea.id}`, "POST", {});
      await loadData();
      setNotice("Ide berhasil disinkronkan ke laporan.");
    });
  };

  const filteredReports = useMemo(() => {
    const normalized = query.toLowerCase();
    return [...reports]
      .sort((a, b) => String(b.date ?? "").localeCompare(String(a.date ?? "")) || Number(b.id) - Number(a.id))
      .filter((report) => (platformFilter === "Semua platform" || platformLabel(report.platform) === platformFilter))
      .filter((report) => `${report.title} ${report.description} ${report.platform} ${report.tag}`.toLowerCase().includes(normalized))
      .filter((report) => period === "semua" || report.period === period);
  }, [period, platformFilter, query, reports]);

  const active = navigation.find((item) => item.id === page)!;
  const insightReports = platformFilter === "Semua platform"
    ? reports
    : reports.filter((report) => platformLabel(report.platform) === platformFilter);
  const totalFollowers = insightReports.reduce((sum, report) => sum + (Number.parseFloat(String(report.followers ?? "").replace(/[^\d.-]/g, "")) || 0), 0);
  const totalViews = insightReports.reduce((sum, report) => sum + (Number.parseFloat(String(report.tayangan ?? "").replace(/[^\d.-]/g, "")) || 0), 0);
  const averageEngagement = insightReports.length
    ? insightReports.reduce((sum, report) => sum + (Number.parseFloat(String(report.engagement ?? "").replace(/[^\d.-]/g, "")) || 0), 0) / insightReports.length
    : 0;
  const saveSettings = (event: FormEvent<HTMLFormElement>, profileImage: string) => {
    event.preventDefault();
    const workspaceName = new FormData(event.currentTarget).get("workspaceName");
    const normalizedName = typeof workspaceName === "string" ? workspaceName.trim() : "";
    if (!normalizedName) {
      setError("Nama workspace wajib diisi.");
      return;
    }
    const snapshot = JSON.stringify({ workspaceName: normalizedName, profileImage });
    try {
      window.localStorage.setItem("planning-kundrew-settings", snapshot);
      window.dispatchEvent(new StorageEvent("storage", { key: "planning-kundrew-settings", newValue: snapshot }));
      setNotice("Pengaturan berhasil disimpan di browser ini.");
    } catch {
      setError("Preferensi tidak dapat disimpan di browser ini. Coba pilih foto yang lebih kecil.");
    }
  };

  return (
    <div className="workspace">
      <aside className="sidebar">
        <Link className="brand" href="/">
          <span className="brand-mark"><Icon name="spark" /></span>
          <span><span className="brand-kicker">CONTENT</span><strong>PLANNING</strong></span>
        </Link>
        <div className="workspace-switcher">
          <WorkspaceAvatar image={profileImage} initial={workspaceInitial} className="workspace-avatar" />
          <span className="workspace-name"><strong>{workspaceName}</strong><small>Workspace pribadi</small></span>
        </div>
        <p className="nav-caption">WORKSPACE</p>
        <nav className="side-nav" aria-label="Navigasi utama">
          {navigation.map((item) => (
            <Link key={item.id} href={item.href} className={`nav-link ${page === item.id ? "active" : ""}`}>
              <span className="nav-icon"><Icon name={item.id} /></span>
              <span className="nav-copy"><strong>{item.label}</strong><small>{item.hint}</small></span>
              {page === item.id && <span className="nav-indicator" />}
            </Link>
          ))}
        </nav>
        <div className="sidebar-bottom">
          <div className="sidebar-tip">
            <span className="tip-icon"><Icon name="spark" /></span>
            <strong>Ide bagus layak terwujud.</strong>
            <p>Mulai dari inspirasi, lanjutkan dengan rencana, ukur dampaknya.</p>
            <Link href="/ide-konten">Buka papan ide <Icon name="arrow" /></Link>
          </div>
        </div>
      </aside>

      <main className="main-content">
        <header className="topbar">
          <div className="breadcrumb"><span>{workspaceName}</span><span className="crumb-slash">/</span><strong>{active.label}</strong></div>
          <div className="topbar-actions"><span className="today-label">{formatDate(dateKey(new Date()), { weekday: "short", day: "numeric", month: "short" })}</span><WorkspaceAvatar image={profileImage} initial={workspaceInitial} className="top-avatar" /></div>
        </header>
        <div className="page-wrap">
          {error && <div className="alert alert-error" role="alert"><span>{error}</span><button type="button"           onClick={() => { setError(""); void loadData(); }}>Coba lagi</button></div>}
          {loading && <div className="loading-banner"><span className="spinner" /> Menghubungkan ke workspace...</div>}
          {!loading && !error && reports.length + schedules.length + ideas.length === 0 && (
            <div className="alert alert-info">Data masih kosong. Tambahkan ide, jadwal, atau laporan untuk mulai menggunakan workspace.</div>
          )}
          {page === "reports" && (
            <ReportsPage
              reports={filteredReports}
              insightReports={insightReports}
              schedules={schedules}
              ideas={ideas}
              totalFollowers={totalFollowers}
              totalViews={totalViews}
              averageEngagement={averageEngagement}
              period={period}
              setPeriod={setPeriod}
              query={query}
              setQuery={setQuery}
              platformFilter={platformFilter}
              setPlatformFilter={setPlatformFilter}
              busy={busy}
              exportCsv={exportCsv}
              openModal={(item) => setModal({ type: "report", item })}
              deleteItem={(item) => removeRecord("reports", item)}
            />
          )}
          {page === "planner" && (
            <PlannerPage
              schedules={schedules}
              busy={busy}
              view={calendarView}
              setView={setCalendarView}
              date={calendarDate}
              setDate={setCalendarDate}
              openModal={(item, date) => setModal({ type: "schedule", item, date })}
              deleteItem={(item) => removeRecord("schedules", item)}
              publish={publishSchedule}
              sync={syncSchedule}
            />
          )}
          {page === "ideas" && (
            <IdeasPage
              ideas={ideas}
              busy={busy}
              openModal={(item, column) => setModal({ type: "idea", item, column })}
              deleteItem={(item) => removeRecord("ideas", item)}
              move={moveIdea}
              sendToPlanner={sendIdeaToPlanner}
              sync={syncIdea}
            />
          )}
          {page === "settings" && (
            <SettingsPage
              key={settingsSnapshot}
              settings={settings}
              databaseConnected={!loading && !error}
              onSave={saveSettings}
            />
          )}
        </div>
      </main>

      {modal && (
        <RecordModal
          key={`${modal.type}-${modal.item?.id ?? "new"}`}
          modal={modal}
          busy={busy}
          onClose={() => setModal(null)}
          onSubmit={saveRecord}
        />
      )}
      {confirmation && (
        <ConfirmationDialog
          confirmation={confirmation}
          busy={busy}
          onCancel={() => { if (!busy) setConfirmation(null); }}
          onConfirm={confirmAction}
        />
      )}
      {notice && <div className="toast"><span className="toast-check">✓</span>{notice}</div>}
    </div>
  );
}

function PageHeading({ eyebrow, title, description, action }: { eyebrow: string; title: string; description: string; action?: React.ReactNode }) {
  return (
    <div className="page-heading">
      <div><p className="eyebrow">{eyebrow}</p><h1>{title}</h1><p className="page-description">{description}</p></div>
      {action}
    </div>
  );
}

function ReportsPage(props: {
  reports: RecordItem[];
  insightReports: RecordItem[];
  schedules: RecordItem[];
  ideas: RecordItem[];
  totalFollowers: number;
  totalViews: number;
  averageEngagement: number;
  period: string;
  setPeriod: (period: string) => void;
  query: string;
  setQuery: (query: string) => void;
  platformFilter: string;
  setPlatformFilter: (platform: string) => void;
  busy: boolean;
  exportCsv: () => void;
  openModal: (item?: RecordItem) => void;
  deleteItem: (item: RecordItem) => void;
}) {
  const [page, setPage] = useState(1);
  const visiblePlatforms = props.platformFilter === "Semua platform" ? platforms : [props.platformFilter];
  const platformCounts = visiblePlatforms.map((platform) => ({
    name: platform,
    count: props.insightReports.filter((item) => platformLabel(item.platform) === platform).length,
  }));
  const maxPlatformCount = Math.max(1, ...platformCounts.map((item) => item.count));
  const pageCount = Math.max(1, Math.ceil(props.reports.length / reportsPerPage));
  const currentPage = Math.min(page, pageCount);
  const pageReports = props.reports.slice((currentPage - 1) * reportsPerPage, currentPage * reportsPerPage);
  const setQuery = (query: string) => {
    setPage(1);
    props.setQuery(query);
  };
  const setPlatformFilter = (platform: string) => {
    setPage(1);
    props.setPlatformFilter(platform);
  };
  const setPeriod = (period: string) => {
    setPage(1);
    props.setPeriod(period);
  };
  return (
    <>
      <PageHeading eyebrow="RINGKASAN PERFORMA" title="Laporan Sosmed" description="Pahami performa konten dan temukan hal yang paling disukai audiens." action={
        <div className="heading-actions"><button className="button button-quiet" type="button" onClick={props.exportCsv}><Icon name="download" /> Ekspor CSV</button><button className="button button-primary" type="button" onClick={() => props.openModal()}><Icon name="plus" /> Tambah laporan</button></div>
      } />
      <div className="welcome-strip"><div className="welcome-orb"><Icon name="spark" /></div><div><strong>Setiap angka punya cerita.</strong><p>Gunakan ringkasan ini untuk melihat konten mana yang membawa dampak paling besar.</p></div><span className="strip-decoration">INSIGHT / 01</span></div>
      <div className="metric-grid">
        <MetricCard label="Total konten" value={formatNumber(props.insightReports.length)} detail="laporan tersimpan" tone="purple" icon="layers" />
        <MetricCard label="Total followers" value={formatNumber(props.totalFollowers)} detail="followers tercatat" tone="blue" icon="users" />
        <MetricCard label="Total tayangan" value={formatNumber(props.totalViews)} detail="tayangan tercatat" tone="peach" icon="eye" />
        <MetricCard label="Rata-rata engagement" value={`${props.averageEngagement.toFixed(1)}%`} detail="interaksi audiens" tone="mint" icon="heart" />
      </div>
      <div className="content-grid analytics-grid">
        <section className="panel trend-panel">
          <div className="panel-heading"><div><p className="eyebrow">OVERVIEW</p><h2>Aktivitas konten</h2></div><div className="segmented">
            {[["mingguan", "Minggu"], ["bulanan", "Bulan"], ["semua", "Semua"]].map(([value, label]) => <button key={value} type="button" className={props.period === value ? "selected" : ""} onClick={() => setPeriod(value)}>{label}</button>)}
          </div></div>
          <div className="chart-area">
            <div className="chart-y-labels"><span>100</span><span>75</span><span>50</span><span>25</span><span>0</span></div>
            <div className="chart-bars" role="img" aria-label="Jumlah laporan konten per platform">
                {platformCounts.map((item, index) => <div className="chart-column" key={item.name}><div className={`chart-bar bar-${index}`} style={{ height: `${Math.max(8, item.count / maxPlatformCount * 100)}%` }}><span>{item.count}</span></div><small className="chart-platform-label"><PlatformIcon platform={item.name} />{item.name}</small></div>)}
              <div className="chart-grid-lines"><i /><i /><i /><i /><i /></div>
            </div>
          </div>
          <div className="chart-legend"><span><i className="legend-dot violet" />Laporan konten</span><span className="chart-footnote">Distribusi berdasarkan platform</span></div>
        </section>
        <section className="panel insight-panel">
          <div className="panel-heading insight-heading"><div><p className="eyebrow">INSIGHT PER PLATFORM</p><h2>{props.platformFilter === "Semua platform" ? "Performa platform" : `Performa ${props.platformFilter}`}</h2></div>
            <select className="insight-filter" value={props.platformFilter} onChange={(event) => setPlatformFilter(event.target.value)} aria-label="Filter insight berdasarkan platform">
              <option>Semua platform</option>{platforms.map((platform) => <option key={platform}>{platform}</option>)}
            </select>
          </div>
          <p className="insight-filter-hint">Filter ini juga berlaku untuk performa terbaru.</p>
          <div className="platform-list">{platformCounts.map((item) => <div className="platform-row" key={item.name}><span className={`platform-symbol ${platformClass(item.name)}`}><PlatformIcon platform={item.name} /></span><span className="platform-row-name">{item.name}</span><div className="mini-track"><i style={{ width: `${item.count ? Math.max(5, item.count / Math.max(props.insightReports.length, 1) * 100) : 0}%` }} /></div><strong>{item.count}</strong></div>)}</div>
          <div className="insight-note"><span>↗</span><p><strong>{props.insightReports.length ? "Konsistensi adalah kunci." : "Mulai catat performa."}</strong><br />{props.insightReports.length ? `Lihat ringkasan konten ${props.platformFilter === "Semua platform" ? "di semua platform" : `di ${props.platformFilter}`}.` : "Tambahkan laporan pertama untuk melihat ringkasan."}</p></div>
        </section>
      </div>
      <section className="panel report-panel">
        <div className="panel-heading report-heading"><div><p className="eyebrow">ARSIP KONTEN</p><h2>Performa terbaru <span className="count-pill">{props.reports.length}</span></h2></div><button type="button" className="text-button" onClick={() => props.openModal()}>Tambah laporan <Icon name="arrow" /></button></div>
        <div className="filter-row">
          <label className="search-box"><Icon name="search" /><input value={props.query} onChange={(event) => setQuery(event.target.value)} placeholder="Cari konten..." /></label>
        </div>
        {props.reports.length === 0 ? <EmptyState title="Belum ada laporan" description="Tambahkan laporan secara manual atau sinkronkan dari kalender dan papan ide." /> : (
          <div className="table-scroll"><table className="data-table"><thead><tr><th>No.</th><th>Konten</th><th>Platform</th><th>Tanggal</th><th>Followers</th><th>Tayangan</th><th>Jangkauan</th><th>Engagement</th><th><span className="sr-only">Aksi</span></th></tr></thead><tbody>
            {pageReports.map((report, index) => <tr key={report.id}>
              <td>{(currentPage - 1) * reportsPerPage + index + 1}</td>
              <td><div className="content-cell"><span className={`platform-avatar ${platformClass(report.platform)}`}><PlatformIcon platform={platformLabel(report.platform)} /></span><span><strong>{report.title}</strong><small>{report.tag || "Konten"} · {report.description || "—"}</small></span></div></td>
              <td><span className={platformClass(report.platform)}><PlatformIcon platform={platformLabel(report.platform)} />{platformLabel(report.platform)}</span></td><td>{formatDate(report.date)}</td><td>{formatNumber(report.followers)}</td><td>{formatNumber(report.tayangan)}</td><td>{formatNumber(report.jangkauan)}</td><td><span className="engagement-value">{report.engagement || "—"}</span></td>
              <td><div className="row-actions"><button title="Edit laporan" aria-label={`Edit laporan ${report.title}`} type="button" onClick={() => props.openModal(report)} disabled={props.busy}><Icon name="edit" /></button><button title="Hapus laporan" aria-label={`Hapus laporan ${report.title}`} type="button" onClick={() => props.deleteItem(report)} disabled={props.busy}><Icon name="trash" /></button></div></td>
            </tr>)}
          </tbody></table></div>
        )}
        {props.reports.length > 0 && (
          <div className="report-pagination">
            <span className="pagination-info">Menampilkan {(currentPage - 1) * reportsPerPage + 1}–{Math.min(currentPage * reportsPerPage, props.reports.length)} dari {props.reports.length} konten</span>
            {pageCount > 1 && (
              <nav className="pagination-controls" aria-label="Navigasi halaman performa terbaru">
                <button type="button" onClick={() => setPage(currentPage - 1)} disabled={currentPage === 1}>Sebelumnya</button>
                {Array.from({ length: pageCount }, (_, index) => index + 1).map((pageNumber) => (
                  <button
                    key={pageNumber}
                    type="button"
                    className={currentPage === pageNumber ? "selected" : ""}
                    aria-label={`Halaman ${pageNumber}`}
                    aria-current={currentPage === pageNumber ? "page" : undefined}
                    onClick={() => setPage(pageNumber)}
                  >
                    {pageNumber}
                  </button>
                ))}
                <button type="button" onClick={() => setPage(currentPage + 1)} disabled={currentPage === pageCount}>Berikutnya</button>
              </nav>
            )}
          </div>
        )}
      </section>
      <div className="bottom-grid">
        <section className="panel bottom-panel"><div className="panel-heading"><div><p className="eyebrow">LANGKAH BERIKUTNYA</p><h2>Yang akan tayang</h2></div><Link className="text-button" href="/content-planner">Buka planner <Icon name="arrow" /></Link></div>
          {props.schedules.length === 0 ? <p className="muted-copy">Belum ada jadwal publikasi.</p> : props.schedules.slice(0, 3).map((schedule) => <div className="compact-row" key={schedule.id}><span className={`platform-avatar ${platformClass(schedule.platform)}`}><PlatformIcon platform={platformLabel(schedule.platform)} /></span><span className="compact-title"><strong>{schedule.title}</strong><small>{formatDate(schedule.date)} · {schedule.time}</small></span><span className={`status-badge status-${schedule.status}`}>{schedule.status === "scheduled" ? "Terjadwal" : schedule.status === "published" ? "Terbit" : "Draft"}</span></div>)}
        </section>
        <section className="panel bottom-panel idea-callout"><span className="callout-graphic">✳</span><p className="eyebrow">KUMPULKAN INSPIRASI</p><h2>Punya ide baru?</h2><p>Simpan ide sebelum terlupa, lalu wujudkan jadi konten berikutnya.</p><Link className="button button-dark" href="/ide-konten">Buka papan ide <Icon name="arrow" /></Link></section>
      </div>
    </>
  );
}

function MetricCard({ label, value, detail, tone, icon }: { label: string; value: string; detail: string; tone: string; icon: string }) {
  return <article className={`metric-card tone-${tone}`}><div className="metric-top"><span>{label}</span><span className="metric-icon"><Icon name={icon} /></span></div><strong className="metric-value">{value}</strong><span className="metric-detail">{detail}</span><span className="metric-decoration" /></article>;
}

function PlannerPage(props: {
  schedules: RecordItem[];
  busy: boolean;
  view: "month" | "week" | "list";
  setView: (view: "month" | "week" | "list") => void;
  date: Date;
  setDate: (date: Date) => void;
  openModal: (item?: RecordItem, date?: string) => void;
  deleteItem: (item: RecordItem) => void;
  publish: (item: RecordItem) => void;
  sync: (item: RecordItem) => void;
}) {
  const mondayOffset = (day: number) => (day + 6) % 7;
  const monthStart = new Date(props.date.getFullYear(), props.date.getMonth(), 1);
  const weekStart = new Date(props.date);
  weekStart.setDate(props.date.getDate() - mondayOffset(props.date.getDay()));
  const gridStart = props.view === "week" ? weekStart : new Date(monthStart.getFullYear(), monthStart.getMonth(), 1 - mondayOffset(monthStart.getDay()));
  const dayCount = props.view === "week" ? 7 : 42;
  const calendarDays = Array.from({ length: dayCount }, (_, index) => {
    const day = new Date(gridStart);
    day.setDate(gridStart.getDate() + index);
    return day;
  });
  const monthLabel = new Intl.DateTimeFormat("id-ID", { month: "long", year: "numeric" }).format(props.date);
  const daysLabel = ["Sen", "Sel", "Rab", "Kam", "Jum", "Sab", "Min"];
  const moveDate = (direction: number) => {
    const next = new Date(props.date);
    next.setDate(next.getDate() + (props.view === "week" ? direction * 7 : 0));
    if (props.view !== "week") next.setMonth(next.getMonth() + direction);
    props.setDate(next);
  };
  const schedulesOn = (date: Date) => props.schedules.filter((schedule) => String(schedule.date).slice(0, 10) === dateKey(date));
  return (
    <>
      <PageHeading eyebrow="JAGA RITME PUBLIKASI" title="Content Planner" description="Atur ritme kontenmu. Satu kalender, semua platform." action={<button className="button button-primary" type="button" onClick={() => props.openModal()}><Icon name="plus" /> Buat jadwal</button>} />
      <div className="planner-summary">
        <div className="planner-summary-copy"><span className="summary-icon"><Icon name="calendar" /></span><span><strong>{props.schedules.length} konten direncanakan</strong><small>Di semua platform sosial media</small></span></div>
        <div className="summary-separator" /><div className="planner-summary-copy"><span className="summary-icon summary-icon-peach"><Icon name="clock" /></span><span><strong>{props.schedules.filter((item) => item.status === "scheduled").length} menunggu publikasi</strong><small>Selesaikan dan catat performanya</small></span></div>
        <Link href="/laporan-sosmed" className="button button-outline summary-link">Lihat laporan <Icon name="arrow" /></Link>
      </div>
      <section className="panel calendar-panel">
        <div className="calendar-toolbar"><div className="calendar-title"><div className="calendar-month-label"><p className="eyebrow">KALENDER KONTEN</p><h2>{props.view === "list" ? "Semua jadwal" : props.view === "week" ? `Minggu · ${monthLabel}` : monthLabel}</h2></div><div className="calendar-nav"><button type="button" onClick={() => moveDate(-1)} aria-label="Sebelumnya">‹</button><button type="button" onClick={() => props.setDate(new Date())}>Hari ini</button><button type="button" onClick={() => moveDate(1)} aria-label="Berikutnya">›</button></div></div>
          <div className="segmented">{(["month", "week", "list"] as const).map((value) => <button key={value} type="button" className={props.view === value ? "selected" : ""} onClick={() => props.setView(value)}>{value === "month" ? "Bulan" : value === "week" ? "Minggu" : "Daftar"}</button>)}</div>
        </div>
        {props.view === "list" ? <div className="schedule-list">{props.schedules.length === 0 ? <EmptyState title="Kalendermu masih kosong" description="Tambahkan jadwal publikasi pertama untuk memulai." /> : [...props.schedules].sort((a, b) => `${a.date} ${a.time}`.localeCompare(`${b.date} ${b.time}`)).map((item) => <ScheduleRow key={item.id} item={item} edit={() => props.openModal(item)} remove={() => props.deleteItem(item)} publish={() => props.publish(item)} sync={() => props.sync(item)} />)}</div> : (
          <div className={`calendar-grid ${props.view === "week" ? "week-grid" : ""}`}>
            {daysLabel.map((label) => <div className="calendar-weekday" key={label}>{label}</div>)}
            {calendarDays.map((date, index) => {
              const dateSchedules = schedulesOn(date);
              return <div key={dateKey(date)} className={`calendar-day ${props.view === "month" && date.getMonth() !== props.date.getMonth() ? "outside-month" : ""} ${dateKey(date) === dateKey(new Date()) ? "today" : ""} ${props.view === "week" ? `week-day-${index}` : ""}`}>
                <div className="day-head"><span>{date.getDate()}</span>{props.view === "week" && <small>{new Intl.DateTimeFormat("id-ID", { month: "short" }).format(date)}</small>}<button type="button" aria-label={`Tambah jadwal ${dateKey(date)}`} onClick={() => props.openModal(undefined, dateKey(date))}>+</button></div>
                <div className="day-events">{dateSchedules.slice(0, 3).map((item) => <button type="button" className={`event-chip event-${item.status}`} key={item.id} onClick={() => props.openModal(item)}><span>{item.time}</span> {item.title}</button>)}{dateSchedules.length > 3 && <small className="more-events">+{dateSchedules.length - 3} lainnya</small>}</div>
              </div>;
            })}
          </div>
        )}
      </section>
      <section className="panel upcoming-panel"><div className="panel-heading"><div><p className="eyebrow">ANTREAN TERDEKAT</p><h2>Siap dipublikasikan</h2></div><span className="count-pill">{props.schedules.filter((item) => item.status === "scheduled").length} jadwal</span></div>
        {props.schedules.filter((item) => item.status === "scheduled").length === 0 ? <p className="muted-copy">Tidak ada jadwal yang menunggu publikasi.</p> : props.schedules.filter((item) => item.status === "scheduled").slice(0, 3).map((item) => <ScheduleRow key={item.id} item={item} edit={() => props.openModal(item)} remove={() => props.deleteItem(item)} publish={() => props.publish(item)} sync={() => props.sync(item)} />)}
      </section>
    </>
  );
}

function ScheduleRow({ item, edit, remove, publish, sync }: { item: RecordItem; edit: () => void; remove: () => void; publish: () => void; sync: () => void }) {
  return <div className="schedule-row"><span className={`platform-avatar ${platformClass(item.platform)}`}><PlatformIcon platform={platformLabel(item.platform)} /></span><span className="schedule-row-main"><strong>{item.title}</strong><small>{formatDate(item.date)} · {item.time} · {platformLabel(item.platform)}</small></span><span className={`status-badge status-${item.status}`}>{item.status === "scheduled" ? "Terjadwal" : item.status === "published" ? "Terbit" : "Draft"}</span><div className="schedule-row-actions"><button type="button" onClick={edit} title="Edit jadwal"><Icon name="edit" /></button>{item.status === "scheduled" && <button type="button" onClick={publish} title="Tandai sudah terbit">Terbit</button>}<button type="button" onClick={sync} title="Sinkronkan ke laporan">Laporan</button><button type="button" onClick={remove} title="Hapus jadwal"><Icon name="trash" /></button></div></div>;
}

function IdeasPage(props: {
  ideas: RecordItem[];
  busy: boolean;
  openModal: (item?: RecordItem, column?: IdeaColumn) => void;
  deleteItem: (item: RecordItem) => void;
  move: (item: RecordItem, column: IdeaColumn) => void;
  sendToPlanner: (item: RecordItem) => void;
  sync: (item: RecordItem) => void;
}) {
  return (
    <>
      <PageHeading eyebrow="TANGKAP SEMUA INSPIRASI" title="Ide Konten" description="Papan kecil untuk ide-ide besar. Kembangkan, jadwalkan, dan wujudkan." action={<button className="button button-primary" type="button" onClick={() => props.openModal()}><Icon name="plus" /> Tambah ide</button>} />
      <div className="ideas-banner"><div className="ideas-banner-symbol"><Icon name="spark" /></div><div><strong>Ide terbaik sering datang tiba-tiba.</strong><p>Simpan di sini, kamu bisa mengembangkannya kapan saja.</p></div><span className="banner-count"><strong>{props.ideas.length}</strong><small>ide tersimpan</small></span></div>
      <div className="kanban-board">{columns.map((column) => {
        const items = props.ideas.filter((idea) => idea.column === column.id);
        return <section className={`kanban-column column-${column.color}`} key={column.id}>
          <div className="kanban-column-heading"><span className={`column-dot dot-${column.color}`} /><h2>{column.label}</h2><span className="count-pill">{items.length}</span><button type="button" title={`Tambah di ${column.label}`} onClick={() => props.openModal(undefined, column.id)}>+</button></div>
          <div className="idea-stack">{items.length === 0 ? <button className="empty-column" type="button" onClick={() => props.openModal(undefined, column.id)}>Belum ada ide di sini <span>+ Tambahkan</span></button> : items.map((idea) => <article className="idea-card" key={idea.id}>
            <div className="idea-card-top"><span className={platformClass(idea.platform)}><PlatformIcon platform={platformLabel(idea.platform)} />{platformLabel(idea.platform)}</span><button type="button" className="idea-edit" onClick={() => props.openModal(idea)} aria-label="Edit ide"><Icon name="edit" /></button></div>
            <h3>{idea.title}</h3><p>{idea.description || "Tambahkan catatan untuk mengembangkan ide ini."}</p>
            <div className="idea-card-meta"><span>{idea.category || "General"}</span>{idea.scheduled_note && <span><Icon name="calendar" /> Terjadwal</span>}</div>
            <div className="idea-card-actions">
              <select aria-label={`Pindahkan ide ${idea.title}`} value={String(idea.column)} onChange={(event) => props.move(idea, event.target.value as IdeaColumn)} disabled={props.busy}>
                {columns.map((option) => <option key={option.id} value={option.id}>Pindah: {option.label}</option>)}
              </select>
              <button type="button" title="Kirim ke planner" onClick={() => props.sendToPlanner(idea)} disabled={props.busy}><Icon name="calendar" /></button>
              <button type="button" title="Buat laporan" onClick={() => props.sync(idea)} disabled={props.busy}><Icon name="chart" /></button>
              <button type="button" title="Hapus ide" onClick={() => props.deleteItem(idea)} disabled={props.busy}><Icon name="trash" /></button>
            </div>
          </article>)}</div>
        </section>;
      })}</div>
    </>
  );
}

function SettingsPage(props: { settings: Record<string, string>; databaseConnected: boolean; onSave: (event: FormEvent<HTMLFormElement>, profileImage: string) => void }) {
  const { settings } = props;
  const [profileImage, setProfileImage] = useState(getProfileImage(settings.profileImage));
  const [photoError, setPhotoError] = useState("");
  const workspaceInitial = settings.workspaceName?.trim().charAt(0).toUpperCase() || "K";

  const chooseProfileImage = async (event: ChangeEvent<HTMLInputElement>) => {
    const input = event.currentTarget;
    const file = input.files?.[0];
    input.value = "";
    if (!file) return;

    try {
      setProfileImage(await resizeProfileImage(file));
      setPhotoError("");
    } catch (error) {
      setPhotoError(error instanceof Error ? error.message : "Foto tidak dapat digunakan.");
    }
  };

  const submitSettings = (event: FormEvent<HTMLFormElement>) => {
    if (photoError) {
      event.preventDefault();
      return;
    }
    props.onSave(event, profileImage);
  };

  return (
    <>
      <PageHeading eyebrow="SESUAIKAN WORKSPACE" title="Pengaturan" description="Atur preferensi agar ruang kerjamu terasa lebih personal." />
      <form className="settings-layout" onSubmit={submitSettings}>
        <div className="settings-main">
          <section className="panel settings-panel">
            <div className="settings-panel-heading"><span className="settings-icon"><Icon name="spark" /></span><div><h2>Foto profil</h2><p>Pilih foto untuk avatar workspace di navigasi dan header.</p></div></div>
            <div className="profile-photo-layout">
              <WorkspaceAvatar image={profileImage} initial={workspaceInitial} className="profile-photo-preview" />
              <div className="profile-photo-controls">
                <label className="field"><span>Pilih foto profil</span><input type="file" accept="image/jpeg,image/png,image/webp,image/gif" onChange={chooseProfileImage} /></label>
                <small className="profile-photo-hint">Gambar akan diperkecil otomatis. Maksimal 10 MB.</small>
                {photoError && <p className="profile-photo-error" role="alert">{photoError}</p>}
                {profileImage && <button className="button button-quiet profile-photo-remove" type="button" onClick={() => { setProfileImage(""); setPhotoError(""); }}>Hapus foto</button>}
              </div>
            </div>
          </section>
          <section className="panel settings-panel"><div className="settings-panel-heading"><span className="settings-icon"><Icon name="spark" /></span><div><h2>Nama workspace</h2><p>Nama ini akan tampil di navigasi dan header seluruh halaman.</p></div></div><div className="settings-fields"><label className="field field-wide"><span>Nama workspace</span><input required maxLength={60} name="workspaceName" defaultValue={settings.workspaceName || "Kundrew Studio"} placeholder="Contoh: Studio Kreatif" /></label></div></section>
          <section className="panel settings-panel"><div className="settings-panel-heading"><span className="settings-icon settings-icon-pink">◎</span><div><h2>Platform tersedia</h2><p>Pilih salah satu saat menambahkan ide, jadwal, atau laporan.</p></div></div><div className="platform-listing">{platforms.map((platform) => <div className="platform-listing-row" key={platform}><span className={`platform-avatar ${platformClass(platform)}`}><PlatformIcon platform={platform} /></span><span><strong>{platform}</strong><small>Tersedia untuk konten workspace</small></span></div>)}</div></section>
          <div className="settings-save"><button className="button button-primary" type="submit" disabled={Boolean(photoError)}>Simpan perubahan <Icon name="arrow" /></button></div>
        </div>
        <aside className="settings-side"><div className="settings-aside-art">✳</div><p className="eyebrow">RUANG KREATIFMU</p><h2>Dirancang untuk ide yang terus bergerak.</h2><p>Preferensi dan foto profil disimpan di browser ini. Data laporan, jadwal, dan ide tersimpan di database.</p><span className={`storage-status ${props.databaseConnected ? "" : "storage-error"}`}><i /> {props.databaseConnected ? "Database terhubung" : "Database belum terhubung"}</span></aside>
      </form>
    </>
  );
}

function EmptyState({ title, description }: { title: string; description: string }) {
  return <div className="empty-state"><span className="empty-state-icon">✳</span><strong>{title}</strong><p>{description}</p></div>;
}

function RecordModal({ modal, busy, onClose, onSubmit }: { modal: Exclude<ModalState, null>; busy: boolean; onClose: () => void; onSubmit: (event: FormEvent<HTMLFormElement>) => void }) {
  const item = modal.item;
  const title = modal.type === "report" ? "laporan" : modal.type === "schedule" ? "jadwal" : "ide";
  const initial: Record<string, string> = modal.type === "report" ? emptyReport : modal.type === "schedule" ? emptySchedule : emptyIdea;
  const value = (key: string) => item?.[key] == null ? "" : String(item[key]);
  return <div className="modal-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) onClose(); }}><section className="modal-card" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div className="modal-heading"><div><p className="eyebrow">{item ? "PERBARUI DATA" : "TAMBAH KE WORKSPACE"}</p><h2 id="modal-title">{item ? "Edit" : "Tambah"} {title}</h2></div><button className="icon-button" type="button" onClick={onClose} aria-label="Tutup"><Icon name="close" /></button></div>
    <form className="modal-form" onSubmit={onSubmit}>
      <label className="field field-wide"><span>Judul</span><input autoFocus required maxLength={255} name="title" defaultValue={value("title") || initial.title} placeholder="Tulis judul konten..." /></label>
      <div className="field-grid"><label className="field"><span>Platform</span><select name="platform" defaultValue={value("platform") ? platformLabel(value("platform")) : initial.platform}>{platforms.map((platform) => <option key={platform}>{platform}</option>)}</select></label>
        {modal.type !== "idea" && <label className="field"><span>Tag / format</span><input name="tag" defaultValue={value("tag") || initial.tag} placeholder="Contoh: Reels" /></label>}
        {modal.type === "idea" && <label className="field"><span>Kategori</span><input name="category" defaultValue={value("category")} placeholder="Contoh: Edukasi" /></label>}
      </div>
      {modal.type === "report" && <div className="field-grid three-fields"><label className="field"><span>Followers</span><input name="followers" defaultValue={value("followers") === "-" ? "" : value("followers")} placeholder="0" /></label><label className="field"><span>Tayangan</span><input name="tayangan" defaultValue={value("tayangan") === "-" ? "" : value("tayangan")} placeholder="0" /></label><label className="field"><span>Jangkauan</span><input name="jangkauan" defaultValue={value("jangkauan") === "-" ? "" : value("jangkauan")} placeholder="0" /></label></div>}
      {modal.type === "report" && <div className="field-grid"><label className="field"><span>Tanggal upload</span><input type="date" required name="date" defaultValue={value("date") || initial.date} /></label><label className="field"><span>Engagement</span><input name="engagement" defaultValue={value("engagement") === "-%" ? "" : value("engagement")} placeholder="4.5%" /></label><label className="field"><span>Periode</span><select name="period" defaultValue={value("period") || initial.period}><option value="mingguan">Mingguan</option><option value="bulanan">Bulanan</option></select></label></div>}
      {modal.type === "schedule" && <div className="field-grid"><label className="field"><span>Tanggal</span><input type="date" required name="date" defaultValue={value("date") || modal.date || initial.date} /></label><label className="field"><span>Waktu</span><input type="time" required name="time" defaultValue={value("time") || initial.time} /></label><label className="field"><span>Status</span><select name="status" defaultValue={value("status") || initial.status}><option value="scheduled">Terjadwal</option><option value="draft">Draft</option><option value="published">Terbit</option></select></label></div>}
      {modal.type === "idea" && <label className="field"><span>Status ide</span><select name="column" defaultValue={value("column") || modal.column || "ideas"}>{columns.map((column) => <option key={column.id} value={column.id}>{column.label}</option>)}</select></label>}
      <label className="field field-wide"><span>Deskripsi</span><textarea rows={3} name="description" defaultValue={value("description")} placeholder="Tambahkan catatan singkat..." /></label>
      <div className="modal-footer"><button type="button" className="button button-quiet" onClick={onClose}>Batal</button><button type="submit" className="button button-primary" disabled={busy}>{busy ? "Menyimpan..." : "Simpan"} <Icon name="arrow" /></button></div>
    </form>
  </section></div>;
}

function ConfirmationDialog({
  confirmation,
  busy,
  onCancel,
  onConfirm,
}: {
  confirmation: Exclude<ConfirmationState, null>;
  busy: boolean;
  onCancel: () => void;
  onConfirm: () => void;
}) {
  const isDelete = confirmation.type === "delete";
  const title = isDelete ? String(confirmation.item.title ?? "data ini") : confirmation.title;
  const heading = isDelete ? "Hapus data ini?" : "Simpan perubahan?";

  return (
    <div className="confirm-backdrop" onMouseDown={(event) => { if (event.target === event.currentTarget) onCancel(); }}>
      <section className="confirm-card" role="alertdialog" aria-modal="true" aria-labelledby="confirm-title" aria-describedby="confirm-description">
        <span className={`confirm-icon ${isDelete ? "confirm-icon-danger" : ""}`}><Icon name={isDelete ? "trash" : "edit"} /></span>
        <p className="eyebrow">{isDelete ? "KONFIRMASI HAPUS" : "KONFIRMASI EDIT"}</p>
        <h2 id="confirm-title">{heading}</h2>
        <p id="confirm-description">
          {isDelete
            ? `Kamu yakin ingin menghapus "${title}"? Tindakan ini tidak dapat dibatalkan.`
            : `Simpan perubahan untuk "${title}"?`}
        </p>
        <div className="confirm-actions">
          <button type="button" className="button button-quiet" onClick={onCancel} disabled={busy} autoFocus>Tidak</button>
          <button type="button" className={`button ${isDelete ? "button-danger" : "button-primary"}`} onClick={onConfirm} disabled={busy}>
            {busy ? "Memproses..." : isDelete ? "Ya, hapus" : "Ya, simpan"}
          </button>
        </div>
      </section>
    </div>
  );
}
