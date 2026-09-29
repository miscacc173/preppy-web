import {
  Calculator, Atom, FlaskConical, Leaf, BookOpen, Pencil, Landmark, Globe,
  Brain, Cpu, LineChart, Briefcase, Languages, Palette, Music,
} from "lucide-vue-next";

export const STUDY_LEVELS = ["O Level", "GCSE", "IGCSE", "AS Level", "A Level"] as const;
export type StudyLevel = (typeof STUDY_LEVELS)[number];

export const BOARDS_BY_LEVEL: Record<StudyLevel, string[]> = {
  "O Level": ["Cambridge (CIE)"],
  GCSE: ["AQA", "Edexcel", "OCR", "WJEC"],
  IGCSE: ["Cambridge (CIE)", "Edexcel", "Oxford AQA"],
  "AS Level": ["Cambridge (CIE)", "Edexcel", "AQA", "OCR"],
  "A Level": ["Cambridge (CIE)", "Edexcel", "AQA", "OCR"],
};

export const YEARS_BY_LEVEL: Record<StudyLevel, string[]> = {
  "O Level": ["Year 9", "Year 10", "Year 11"],
  GCSE: ["Year 9", "Year 10", "Year 11"],
  IGCSE: ["Year 9", "Year 10", "Year 11"],
  "AS Level": ["Year 12"],
  "A Level": ["Year 12", "Year 13"],
};

export const GRADES_BY_LEVEL: Record<StudyLevel, string[]> = {
  "O Level": ["A*", "A", "B", "C", "D"],
  GCSE: ["9", "8", "7", "6", "5", "4"],
  IGCSE: ["A*", "A", "B", "C", "D"],
  "AS Level": ["A", "B", "C", "D", "E"],
  "A Level": ["A*", "A", "B", "C", "D"],
};

export const SUBJECTS = [
  { name: "Mathematics", icon: Calculator },
  { name: "Further Mathematics", icon: Calculator },
  { name: "Physics", icon: Atom },
  { name: "Chemistry", icon: FlaskConical },
  { name: "Biology", icon: Leaf },
  { name: "English Literature", icon: BookOpen },
  { name: "English Language", icon: Pencil },
  { name: "History", icon: Landmark },
  { name: "Geography", icon: Globe },
  { name: "Psychology", icon: Brain },
  { name: "Computer Science", icon: Cpu },
  { name: "Economics", icon: LineChart },
  { name: "Business", icon: Briefcase },
  { name: "Modern Languages", icon: Languages },
  { name: "Art & Design", icon: Palette },
  { name: "Music", icon: Music },
] as const;

export const CONFIDENCE = [
  { id: "low", label: "Need a lot of support" },
  { id: "mid", label: "Somewhat confident" },
  { id: "high", label: "Confident" },
] as const;
export type Confidence = (typeof CONFIDENCE)[number]["id"];

export const MONTHS = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];
