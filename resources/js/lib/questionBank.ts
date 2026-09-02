export type MCQ = { q: string; opts: string[]; a: number };

export const SUBJECTS = ["Physics", "Chemistry", "Mathematics", "Biology", "Economics", "Business"] as const;
export type Subject = (typeof SUBJECTS)[number];

export const TOPICS: Record<Subject, string[]> = {
  Physics: ["Mechanics", "Electricity", "Waves", "Quantum", "Thermodynamics"],
  Chemistry: ["Organic", "Inorganic", "Physical", "Equilibria"],
  Mathematics: ["Pure", "Mechanics", "Statistics"],
  Biology: ["Cells", "Genetics", "Ecology", "Physiology"],
  Economics: ["Microeconomics", "Macroeconomics", "International trade"],
  Business: ["Marketing", "Finance", "Operations"],
};

export const QUESTION_BANK: Record<Subject, MCQ[]> = {
  Physics: [
    { q: "A car of mass 1200 kg accelerates from rest to 25 m/s in 8 s. Calculate the resultant force.", opts: ["3,750 N", "300 N", "30,000 N", "9,600 N"], a: 0 },
    { q: "Which of the following is a vector quantity?", opts: ["Mass", "Speed", "Velocity", "Energy"], a: 2 },
    { q: "A capacitor of 10 μF is charged to 12 V. What is the energy stored?", opts: ["120 μJ", "720 μJ", "1.44 mJ", "60 μJ"], a: 1 },
    { q: "Newton's third law states that...", opts: ["F = ma", "Forces come in equal and opposite pairs", "Energy is conserved", "Momentum is conserved"], a: 1 },
    { q: "The unit of magnetic flux density is:", opts: ["Weber", "Tesla", "Henry", "Farad"], a: 1 },
    { q: "Which particle has no charge?", opts: ["Electron", "Proton", "Neutron", "Positron"], a: 2 },
    { q: "The photoelectric effect demonstrates that light:", opts: ["Has wave properties only", "Has particle properties", "Travels in straight lines", "Refracts"], a: 1 },
    { q: "Doubling the amplitude of a wave changes intensity by a factor of:", opts: ["2", "4", "0.5", "1"], a: 1 },
    { q: "Kirchhoff's voltage law conserves:", opts: ["Charge", "Current", "Energy", "Mass"], a: 2 },
    { q: "Half-life of a radioactive sample depends on:", opts: ["Mass", "Temperature", "Pressure", "Nature of isotope"], a: 3 },
  ],
  Chemistry: [
    { q: "The pH of a 0.01 mol/dm³ HCl solution is:", opts: ["1", "2", "12", "7"], a: 1 },
    { q: "Which is an isomer of butane?", opts: ["Propane", "Methylpropane", "Pentane", "Ethene"], a: 1 },
    { q: "A catalyst increases reaction rate by:", opts: ["Raising temperature", "Lowering activation energy", "Increasing pressure", "Adding more reactant"], a: 1 },
    { q: "The functional group of an alcohol is:", opts: ["−COOH", "−OH", "−CHO", "−NH₂"], a: 1 },
    { q: "Group 1 metals get more reactive down the group because:", opts: ["Atoms get smaller", "Outer electron is held more loosely", "Nuclear charge decreases", "Shielding decreases"], a: 1 },
    { q: "Which bond is broken when ice melts?", opts: ["Covalent", "Ionic", "Hydrogen bond", "Metallic"], a: 2 },
    { q: "Le Chatelier's principle predicts that increasing pressure on N₂ + 3H₂ ⇌ 2NH₃ will:", opts: ["Shift left", "Shift right", "No effect", "Stop the reaction"], a: 1 },
    { q: "The oxidation state of S in H₂SO₄ is:", opts: ["+2", "+4", "+6", "−2"], a: 2 },
    { q: "Electrolysis of brine produces:", opts: ["Hydrogen and oxygen", "Chlorine, hydrogen and NaOH", "Sodium metal", "Sulfuric acid"], a: 1 },
    { q: "Which reagent distinguishes an aldehyde from a ketone?", opts: ["Bromine water", "Tollens' reagent", "Litmus", "NaCl"], a: 1 },
  ],
  Mathematics: [
    { q: "Differentiate y = 3x² + 2x with respect to x.", opts: ["6x + 2", "3x + 2", "6x", "x² + 2"], a: 0 },
    { q: "Solve: 2x + 5 = 17.", opts: ["x = 5", "x = 6", "x = 7", "x = 11"], a: 1 },
    { q: "The integral ∫ 2x dx equals:", opts: ["x² + C", "2 + C", "x + C", "2x² + C"], a: 0 },
    { q: "sin(30°) equals:", opts: ["½", "√3/2", "1", "√2/2"], a: 0 },
    { q: "The discriminant of x² − 4x + 4 is:", opts: ["0", "4", "−4", "16"], a: 0 },
    { q: "If log₂ x = 5, then x =", opts: ["10", "25", "32", "16"], a: 2 },
    { q: "Mean of 2, 4, 6, 8, 10:", opts: ["5", "6", "7", "8"], a: 1 },
    { q: "Vector magnitude of (3, 4):", opts: ["5", "7", "12", "25"], a: 0 },
    { q: "Probability of an even number on one die roll:", opts: ["1/6", "1/3", "1/2", "2/3"], a: 2 },
    { q: "The sum of an infinite GP with a = 1, r = ½ is:", opts: ["1", "2", "½", "∞"], a: 1 },
  ],
  Biology: [
    { q: "The site of protein synthesis is:", opts: ["Nucleus", "Mitochondrion", "Ribosome", "Lysosome"], a: 2 },
    { q: "In a monohybrid cross of two heterozygotes, the phenotypic ratio is:", opts: ["1:1", "3:1", "9:3:3:1", "1:2:1"], a: 1 },
    { q: "Photosynthesis converts:", opts: ["Glucose to oxygen", "CO₂ and water to glucose", "Glucose to CO₂", "ATP to glucose"], a: 1 },
    { q: "Which structure controls what enters/leaves the cell?", opts: ["Cell wall", "Cell membrane", "Nucleus", "Cytoplasm"], a: 1 },
    { q: "Insulin is produced by the:", opts: ["Liver", "Kidneys", "Pancreas", "Adrenal gland"], a: 2 },
    { q: "DNA replication is:", opts: ["Conservative", "Semi-conservative", "Dispersive", "Random"], a: 1 },
    { q: "A producer in a food chain is typically a:", opts: ["Herbivore", "Carnivore", "Plant", "Decomposer"], a: 2 },
    { q: "Enzymes work best at their:", opts: ["Highest temperature", "Optimum pH and temperature", "Lowest pH", "Any condition"], a: 1 },
    { q: "Active transport requires:", opts: ["No energy", "Diffusion", "ATP", "Osmosis"], a: 2 },
    { q: "Mitosis produces:", opts: ["Four haploid cells", "Two identical diploid cells", "Two haploid cells", "Gametes"], a: 1 },
  ],
  Economics: [
    { q: "The law of demand states that, ceteris paribus:", opts: ["Price and quantity demanded rise together", "Price and quantity demanded move inversely", "Demand is constant", "Supply determines demand"], a: 1 },
    { q: "An example of a public good is:", opts: ["A loaf of bread", "Street lighting", "A private car", "A cinema ticket"], a: 1 },
    { q: "Inflation measured by CPI tracks:", opts: ["Wages only", "A basket of consumer goods and services", "Stock prices", "Interest rates"], a: 1 },
    { q: "If PED = 0.4, demand is:", opts: ["Elastic", "Inelastic", "Unit elastic", "Perfectly elastic"], a: 1 },
    { q: "Opportunity cost is:", opts: ["The money cost", "The next best alternative forgone", "Total spending", "Average cost"], a: 1 },
    { q: "A government deficit means:", opts: ["Spending < tax revenue", "Spending > tax revenue", "No taxation", "Trade surplus"], a: 1 },
    { q: "Comparative advantage is based on:", opts: ["Absolute output", "Lower opportunity cost", "Population size", "Currency strength"], a: 1 },
    { q: "Central banks usually raise interest rates to:", opts: ["Stimulate growth", "Reduce inflation", "Increase exports", "Weaken the currency"], a: 1 },
    { q: "A negative externality leads to:", opts: ["Over-consumption relative to social optimum", "Under-consumption", "Perfect equilibrium", "Higher welfare"], a: 0 },
    { q: "Monopoly is characterised by:", opts: ["Many sellers", "One dominant seller", "Free entry", "Identical products"], a: 1 },
  ],
  Business: [
    { q: "The 4 Ps of the marketing mix are:", opts: ["Price, Product, Place, Promotion", "People, Profit, Plan, Process", "Product, Profit, Promotion, People", "Price, Plan, Place, People"], a: 0 },
    { q: "Gross profit equals:", opts: ["Revenue − cost of sales", "Revenue − all expenses", "Revenue + COGS", "Revenue × margin"], a: 0 },
    { q: "Cash flow is:", opts: ["Profit after tax", "Money in and out of the business", "Total assets", "Shareholder equity"], a: 1 },
    { q: "A SWOT analysis covers:", opts: ["Strengths, Weaknesses, Opportunities, Threats", "Sales, Wages, Output, Tax", "Stock, Workers, Orders, Time", "Supply, Workforce, Operations, Trade"], a: 0 },
    { q: "Just-in-time (JIT) production aims to:", opts: ["Increase stock holding", "Reduce inventory and waste", "Increase fixed costs", "Slow throughput"], a: 1 },
    { q: "Break-even point is where:", opts: ["Total revenue = total costs", "Profit is maximum", "Variable cost is zero", "Sales are zero"], a: 0 },
    { q: "A limited company offers shareholders:", opts: ["Unlimited liability", "Limited liability", "No ownership", "Tax exemption"], a: 1 },
    { q: "Market segmentation divides customers by:", opts: ["Random selection", "Shared characteristics", "Geography only", "Alphabet"], a: 1 },
    { q: "Economies of scale lead to:", opts: ["Lower average cost as output rises", "Higher fixed cost", "Lower revenue", "No effect on cost"], a: 0 },
    { q: "An autocratic leadership style is:", opts: ["Highly delegated", "Leader-centred decisions", "Consensus driven", "Laissez-faire"], a: 1 },
  ],
};

export const EXAMINER_NOTES: Record<Subject, string> = {
  Physics: "Strong calculation. Lose marks on phrasing — train 'equal and opposite' and 'along the same line of action'.",
  Chemistry: "Mechanism arrows and oxidation-state working need sharper labelling; balance equations before stating the species.",
  Mathematics: "Method marks dropped through skipped working. Show every line — examiners credit chains of reasoning.",
  Biology: "Definitions are vague. Use exact mark-scheme keywords: 'partially permeable', 'semi-conservative', 'down a concentration gradient'.",
  Economics: "Diagrams correct, evaluation thin. Always end with a judgement supported by magnitude or context.",
  Business: "Application to the case study is missing. Quote figures from the stem and link directly to the recommendation.",
};

export const WEAK_AREAS: Record<Subject, { t: string; v: number }[]> = {
  Physics: [
    { t: "Quantum physics phrasing", v: 44 },
    { t: "Directional vocabulary", v: 58 },
    { t: "Energy conservation arguments", v: 61 },
  ],
  Chemistry: [
    { t: "Organic mechanism arrows", v: 48 },
    { t: "Equilibria explanation", v: 55 },
    { t: "Oxidation states", v: 63 },
  ],
  Mathematics: [
    { t: "Integration by parts", v: 50 },
    { t: "Trigonometric identities", v: 58 },
    { t: "Probability tree diagrams", v: 65 },
  ],
  Biology: [
    { t: "Genetics ratios", v: 46 },
    { t: "Hormonal control vocabulary", v: 57 },
    { t: "Photosynthesis stages", v: 62 },
  ],
  Economics: [
    { t: "Evaluation depth", v: 49 },
    { t: "Macro policy trade-offs", v: 56 },
    { t: "Elasticity calculation", v: 64 },
  ],
  Business: [
    { t: "Case-study application", v: 47 },
    { t: "Ratio interpretation", v: 58 },
    { t: "Justified recommendation", v: 63 },
  ],
};
