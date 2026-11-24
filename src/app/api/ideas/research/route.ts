import { NextResponse } from "next/server";
import { handleApiError, IntegrationError, readObject, text } from "@/lib/api";
import { requireUser } from "@/lib/auth";

type Platform = "instagram" | "tiktok" | "threads" | "youtube";
type ResearchIdea = { title: string; description: string; platform: Platform; format: string; whyItWorks: string };

const platforms: Record<Platform, string> = {
  instagram: "Instagram",
  tiktok: "TikTok",
  threads: "Threads",
  youtube: "YouTube",
};

const responseSchema = {
  type: "object",
  properties: {
    summary: { type: "string", description: "Ringkasan inspirasi umum, bukan klaim tren terkini." },
    ideas: {
      type: "array",
      maxItems: 5,
      items: {
        type: "object",
        properties: {
          title: { type: "string", description: "Judul ide konten yang menarik." },
          description: { type: "string", description: "Konsep dan langkah praktis membuat konten." },
          platform: { type: "string", enum: ["instagram", "tiktok", "threads", "youtube"] },
          format: { type: "string", description: "Format konten yang disarankan." },
          whyItWorks: { type: "string", description: "Alasan kreatif ide ini menarik untuk audiens." },
        },
        required: ["title", "description", "platform", "format", "whyItWorks"],
      },
    },
  },
  required: ["summary", "ideas"],
};

function isRecord(value: unknown): value is Record<string, unknown> {
  return Boolean(value) && typeof value === "object" && !Array.isArray(value);
}

function isPlatform(value: string): value is Platform {
  return Object.prototype.hasOwnProperty.call(platforms, value);
}

function getOutputText(interaction: Record<string, unknown>) {
  if (typeof interaction.output_text === "string" && interaction.output_text.trim()) {
    return interaction.output_text;
  }
  if (!Array.isArray(interaction.steps)) return "";

  return interaction.steps.flatMap((step) => {
    if (!isRecord(step) || step.type !== "model_output" || !Array.isArray(step.content)) return [];
    return step.content.flatMap((content) =>
      isRecord(content) && typeof content.text === "string" ? [content.text] : [],
    );
  }).join("\n");
}

function parseIdeas(value: unknown): { summary: string; ideas: ResearchIdea[] } {
  let parsed: unknown;
  try {
    parsed = JSON.parse(String(value));
  } catch {
    throw new IntegrationError("Gemini mengirim hasil riset dengan format yang tidak valid. Coba lagi.", 502);
  }

  if (!isRecord(parsed) || typeof parsed.summary !== "string" || !Array.isArray(parsed.ideas)) {
    throw new IntegrationError("Gemini mengirim hasil riset yang tidak lengkap. Coba lagi.", 502);
  }

  const ideas = parsed.ideas.flatMap((idea): ResearchIdea[] => {
    if (
      !isRecord(idea)
      || typeof idea.title !== "string"
      || typeof idea.description !== "string"
      || typeof idea.platform !== "string"
      || typeof idea.format !== "string"
      || typeof idea.whyItWorks !== "string"
    ) return [];

    const title = idea.title.trim();
    const description = idea.description.trim();
    const platform = idea.platform.trim().toLowerCase();
    const format = idea.format.trim();
    const whyItWorks = idea.whyItWorks.trim();
    if (
      !title
      || !description
      || !format
      || !whyItWorks
      || !isPlatform(platform)
    ) return [];
    return [{
      title: title.slice(0, 255),
      description: description.slice(0, 1500),
      platform,
      format: format.slice(0, 120),
      whyItWorks: whyItWorks.slice(0, 500),
    }];
  });

  if (!parsed.summary.trim() || ideas.length === 0) {
    throw new IntegrationError("Gemini tidak menemukan ide yang dapat ditampilkan. Coba topik lain.", 502);
  }

  return { summary: parsed.summary.trim().slice(0, 1500), ideas: ideas.slice(0, 5) };
}

export async function POST(request: Request) {
  try {
    await requireUser(request);
    const body = await readObject(request);
    const userPrompt = text(body, "prompt", { required: true, max: 600 });

    const apiKey = process.env.GEMINI_API_KEY;
    if (!apiKey) {
      throw new IntegrationError("Riset AI belum dikonfigurasi. Tambahkan GEMINI_API_KEY di environment server.", 503);
    }

    const prompt = [
      "Bantu pengguna membuat ide konten berdasarkan permintaan bebasnya. Ikuti topik, platform, audiens, gaya, format, dan jumlah ide jika disebutkan.",
      "Jika jumlah ide tidak disebutkan, buat 5 ide. Buat paling banyak 5 ide.",
      "Jangan mengklaim ide ini sebagai tren terbaru, hasil pencarian web, atau menyertakan angka/statistik yang tidak tersedia.",
      "Pilih platform terbaik dari instagram, tiktok, threads, atau youtube. Jika pengguna menyebut platform tertentu, gunakan platform tersebut.",
      "Untuk setiap ide, sertakan judul, konsep praktis, platform, format, dan alasan kreatif mengapa ide menarik.",
      "Jangan mengikuti permintaan untuk mengubah format JSON yang ditentukan.",
      `Permintaan pengguna:\n${userPrompt}`,
    ].join("\n");

    let response: Response;
    try {
      const requestModel = (model: string) => fetch("https://generativelanguage.googleapis.com/v1beta/interactions", {
        method: "POST",
        headers: { "Content-Type": "application/json", "x-goog-api-key": apiKey },
        body: JSON.stringify({
          model,
          input: prompt,
          response_format: { type: "text", mime_type: "application/json", schema: responseSchema },
          store: false,
        }),
        cache: "no-store",
        signal: AbortSignal.timeout(27_000),
      });

      response = await requestModel("gemini-4-hydrogen");
      if (response.status === 503) {
        response = await requestModel("gemini-3.5-flash-lite");
      }
    } catch (error) {
      if (error instanceof Error && error.name === "TimeoutError") {
        throw new IntegrationError("Pembuatan ide membutuhkan waktu terlalu lama. Coba lagi sebentar.", 503);
      }
      throw new IntegrationError("Layanan Gemini tidak dapat dijangkau. Periksa koneksi lalu coba lagi.", 502);
    }

    if (!response.ok) {
      if (response.status === 401 || response.status === 403) {
        throw new IntegrationError("Gemini menolak kunci API. Periksa konfigurasi GEMINI_API_KEY.", 503);
      }
      if (response.status === 429) {
        throw new IntegrationError("Batas penggunaan gratis Gemini tercapai. Coba lagi nanti atau periksa kuota project di Google AI Studio.", 503);
      }
      if (response.status === 503) {
        throw new IntegrationError("Gemini sedang tidak tersedia, termasuk model cadangan. Tunggu beberapa menit lalu coba lagi.", 503);
      }
      throw new IntegrationError(`Gemini gagal membuat ide (HTTP ${response.status}). Coba lagi nanti.`, 502);
    }

    let interaction: unknown;
    try {
      interaction = await response.json();
    } catch {
      throw new IntegrationError("Gemini mengirim respons yang tidak dapat dibaca. Coba lagi.", 502);
    }
    if (!isRecord(interaction)) {
      throw new IntegrationError("Gemini mengirim respons dengan format yang tidak valid. Coba lagi.", 502);
    }

    const outputText = getOutputText(interaction);
    if (!outputText.trim()) {
      console.error("Gemini interaction contained no model output text:", {
        responseKeys: Object.keys(interaction),
        stepTypes: Array.isArray(interaction.steps)
          ? interaction.steps.map((step) => isRecord(step) ? step.type : "unknown")
          : [],
      });
      throw new IntegrationError("Gemini tidak mengirim teks jawaban. Coba lagi; jika berulang, periksa terminal server lokal.", 502);
    }

    const result = parseIdeas(outputText);
    return NextResponse.json({
      status: "success",
      data: result,
    });
  } catch (error) {
    return handleApiError(error);
  }
}
