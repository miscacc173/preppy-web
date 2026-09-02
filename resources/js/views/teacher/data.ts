import {
  Calculator, Atom, FlaskConical, Leaf, BookOpen, Pencil, Landmark, Globe,
  Brain, Cpu, LineChart, Briefcase, Languages, Palette, Music,
  ClipboardList, Wand2, Target, TrendingUp, AlertCircle, Sparkles, Library, BookMarked,
} from "lucide-vue-next";

export const QUALIFICATIONS = ["GCSE", "IGCSE", "O Level", "AS Level", "A Level"] as const;
export type Qualification = (typeof QUALIFICATIONS)[number];

export const BOARDS_BY_QUAL: Record<Qualification, string[]> = {
  GCSE: ["AQA", "Edexcel", "OCR", "WJEC"],
  IGCSE: ["Cambridge (CIE)", "Edexcel", "Oxford AQA"],
  "O Level": ["Cambridge (CIE)", "Edexcel"],
  "AS Level": ["Cambridge (CIE)", "Edexcel", "AQA", "OCR"],
  "A Level": ["Cambridge (CIE)", "Edexcel", "AQA", "OCR"],
};

export const YEARS_BY_QUAL: Record<Qualification, string[]> = {
  GCSE: ["Year 9", "Year 10", "Year 11"],
  IGCSE: ["Year 9", "Year 10", "Year 11"],
  "O Level": ["Year 10", "Year 11"],
  "AS Level": ["Year 12"],
  "A Level": ["Year 12", "Year 13"],
};

export const ROLES = ["Subject Teacher", "Tutor", "Head of Department", "Academic Coordinator", "Other"];

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

export const PRIORITIES = [
  { id: "create-tests", label: "Create Tests & Assessments", desc: "Build exam-board-aligned papers quickly.", icon: ClipboardList },
  { id: "create-activities", label: "Create Learning Activities", desc: "Design guided lessons and practice.", icon: Wand2 },
  { id: "assign-practice", label: "Assign Practice to Students", desc: "Target the topics that need work.", icon: Target },
  { id: "track-progress", label: "Track Student Progress", desc: "See engagement and performance at a glance.", icon: TrendingUp },
  { id: "identify-weak", label: "Identify Weak Topics", desc: "Spot class and individual gaps early.", icon: AlertCircle },
  { id: "teaching-support", label: "Generate Teaching Support", desc: "AI help with explanations and examples.", icon: Sparkles },
  { id: "explore-resources", label: "Explore Subject Resources", desc: "Curated materials aligned to your board.", icon: Library },
  { id: "own-learning", label: "Continue My Own Learning", desc: "Keep your subject knowledge sharp.", icon: BookMarked },
] as const;
