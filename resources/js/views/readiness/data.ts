export const RADAR_COLORS = ["#22c1d6", "#7b6cf0", "#9b87f5", "#5b8def", "#e85d9c", "#c25fb3"];

export type Status = "Excellent" | "Strong" | "Good" | "Developing" | "Needs Improvement" | "Priority Focus";

export function getStatus(score: number): { label: Status; tone: string } {
  if (score >= 85) return { label: "Excellent", tone: "mint" };
  if (score >= 75) return { label: "Strong", tone: "mint" };
  if (score >= 70) return { label: "Good", tone: "blue" };
  if (score >= 65) return { label: "Developing", tone: "gold" };
  if (score >= 60) return { label: "Needs Improvement", tone: "gold" };
  return { label: "Priority Focus", tone: "pink" };
}

export const SKILLS = [
  {
    short: "Recall", full: "Knowledge Recall", score: 78, benchmark: 88, classAvg: 70, color: RADAR_COLORS[0],
    why: "Measures how reliably you retrieve facts, definitions, and formulae under exam conditions.",
    action: "Run a 10-minute flashcard session on weak definitions, 3× this week.",
  },
  {
    short: "Application", full: "Application", score: 64, benchmark: 85, classAvg: 67, color: RADAR_COLORS[1],
    why: "Measures your ability to apply concepts to unfamiliar exam questions.",
    action: "Complete 3 scenario-based practice questions this week.",
  },
  {
    short: "Analysis", full: "Analysis", score: 71, benchmark: 84, classAvg: 68, color: RADAR_COLORS[2],
    why: "Measures how well you break problems into parts and identify what each mark rewards.",
    action: "Annotate 2 past-paper questions with mark-scheme reasoning.",
  },
  {
    short: "Calculation", full: "Calculation", score: 88, benchmark: 90, classAvg: 72, color: RADAR_COLORS[3],
    why: "Measures accuracy, working out, and correct units across quantitative questions.",
    action: "Maintain pace — try a timed numerical set to lock in the A* level.",
  },
  {
    short: "Reasoning", full: "Written Reasoning", score: 58, benchmark: 82, classAvg: 65, color: RADAR_COLORS[4],
    why: "Measures clarity and precision of written explanations — the language examiners reward.",
    action: "Rewrite 3 of your weakest 4-mark answers using mark-scheme phrasing.",
  },
  {
    short: "Diagrams", full: "Diagram Interpretation", score: 67, benchmark: 83, classAvg: 66, color: RADAR_COLORS[5],
    why: "Measures how accurately you read, label, and draw diagrams to earn structure marks.",
    action: "Practise labelling 5 past-paper diagrams without notes.",
  },
];

export const strongest = [...SKILLS].sort((a, b) => b.score - a.score)[0];
export const weakest = [...SKILLS].sort((a, b) => a.score - b.score)[0];
