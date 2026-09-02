export type Tone = "blue" | "violet" | "mint" | "gold";

export const toneMap: Record<Tone, { chip: string; icon: string; iconBg: string; glow: string; dot: string }> = {
  blue: { chip: "bg-blue-light text-blue", icon: "text-blue", iconBg: "bg-blue-light", glow: "from-blue/20", dot: "bg-blue" },
  violet: { chip: "bg-violet-light text-violet", icon: "text-violet", iconBg: "bg-violet-light", glow: "from-violet/20", dot: "bg-violet" },
  mint: { chip: "bg-mint-light text-mint", icon: "text-mint", iconBg: "bg-mint-light", glow: "from-mint/25", dot: "bg-mint" },
  gold: { chip: "bg-gold-light text-gold", icon: "text-gold", iconBg: "bg-gold-light", glow: "from-gold/25", dot: "bg-gold" },
};
