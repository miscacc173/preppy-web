export { default as TestimonialsSection } from "./TestimonialsSection.vue";

export interface Testimonial {
  quote: string;
  name: string;
  initials: string;
  subject: string;
  board: string;
  improvement: string;
  tone: "blue" | "violet" | "mint" | "gold" | "pink";
}

export const TONE = {
  blue: { bg: "bg-blue-light", text: "text-blue", border: "border-blue-mid" },
  violet: { bg: "bg-violet-light", text: "text-violet", border: "border-[color:var(--border-mid)]" },
  mint: { bg: "bg-mint-light", text: "text-mint", border: "border-[color:var(--border-mid)]" },
  gold: { bg: "bg-gold-light", text: "text-gold", border: "border-[color:var(--border-mid)]" },
  pink: { bg: "bg-pink-light", text: "text-pink", border: "border-[color:var(--border-mid)]" },
} as const;

export const TESTIMONIALS: Testimonial[] = [
  { quote: "The mark-scheme feedback is what finally made it click. I could see exactly which mark points I was missing on every 6-marker.", name: "Aisha Rahman", initials: "AR", subject: "A Level Biology", board: "AQA", improvement: "C → A*", tone: "blue" },
  { quote: "Honestly the diagnostic in 6 minutes told me more than a whole term of mocks. I stopped revising what I already knew.", name: "Ahmed Khan", initials: "AK", subject: "IGCSE Physics", board: "Cambridge", improvement: "+2 Grades", tone: "mint" },
  { quote: "The examiner-style comments are the closest thing to having a real tutor. It caught phrasing issues I would never have spotted.", name: "Zara Hussain", initials: "ZH", subject: "A Level Chemistry", board: "OCR", improvement: "B → A", tone: "violet" },
  { quote: "I went from panicking about paper 4 to actually enjoying practice. The pacing analytics changed how I approach long questions.", name: "Bilal Ahmad", initials: "BA", subject: "A Level Maths", board: "Edexcel", improvement: "A → A*", tone: "gold" },
  { quote: "I love how it shows the top-scoring answer next to mine. Seeing the difference line by line is way more useful than a grade.", name: "Fatima Sheikh", initials: "FS", subject: "O Level English Lit", board: "Cambridge", improvement: "+1 Grade", tone: "pink" },
  { quote: "Live grade prediction kept me motivated. Watching it climb week by week is the reason I actually kept going.", name: "Hamza Ali", initials: "HA", subject: "A Level Economics", board: "AQA", improvement: "D → A", tone: "blue" },
  { quote: "As a teacher, the class readiness view saved me hours. I know exactly which topics to reteach before every mock.", name: "Ms. Sara Iqbal", initials: "SI", subject: "Head of Sciences", board: "IB", improvement: "Class avg +18%", tone: "violet" },
  { quote: "I'm a self-learner and didn't have anyone to check my answers. AiStar filled that gap completely.", name: "Usman Farooq", initials: "UF", subject: "IGCSE Additional Maths", board: "Cambridge", improvement: "B → A*", tone: "mint" },
  { quote: "The organic chemistry feedback is scary accurate. It caught arrow-pushing mistakes my school notes never mentioned.", name: "Hira Malik", initials: "HM", subject: "A Level Chemistry", board: "Edexcel", improvement: "C → A", tone: "gold" },
  { quote: "Every answer scored against the real mark scheme — I finally stopped guessing what examiners actually wanted.", name: "Ali Raza", initials: "AR", subject: "A Level Physics", board: "OCR", improvement: "+2 Grades", tone: "blue" },
  { quote: "Practicing with AiStar felt like having a mock every day, but without the anxiety. Confidence built up naturally.", name: "Mariam Qureshi", initials: "MQ", subject: "IGCSE Business", board: "Cambridge", improvement: "B → A*", tone: "pink" },
  { quote: "The topic diagnostics pointed straight at my Fields weakness. Two weeks later that was my strongest topic.", name: "Omar Siddiqui", initials: "OS", subject: "A Level Physics", board: "AQA", improvement: "A → A*", tone: "violet" },
];
