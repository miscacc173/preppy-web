<script setup lang="ts">
import type { QuestionVisualSpec } from ".";
import BeamDiagram from "./BeamDiagram.vue";
import IVCurve from "./IVCurve.vue";
import CircuitDiagram from "./CircuitDiagram.vue";
import Projectile from "./Projectile.vue";
import ChemStructure from "./ChemStructure.vue";
import ChemApparatus from "./ChemApparatus.vue";
import BiologyCell from "./BiologyCell.vue";
import FoodWeb from "./FoodWeb.vue";
import MathsGraph from "./MathsGraph.vue";
import MathsTriangle from "./MathsTriangle.vue";
import GeographyMap from "./GeographyMap.vue";
import TableFigure from "./TableFigure.vue";
import FormulaFigure from "./FormulaFigure.vue";

defineProps<{ spec: QuestionVisualSpec; class?: string }>();

const COMPONENTS: Record<string, unknown> = {
  "physics-beam": BeamDiagram,
  "physics-iv-curve": IVCurve,
  "physics-circuit": CircuitDiagram,
  "physics-projectile": Projectile,
  "chemistry-structure": ChemStructure,
  "chemistry-apparatus": ChemApparatus,
  "biology-cell": BiologyCell,
  "biology-foodweb": FoodWeb,
  "maths-graph": MathsGraph,
  "maths-triangle": MathsTriangle,
  "geography-map": GeographyMap,
};
</script>

<template>
  <TableFigure v-if="spec.kind === 'table'" :rows="spec.rows ?? []" :class="$props.class" />
  <FormulaFigure v-else-if="spec.kind === 'formula'" :text="spec.formula ?? ''" :class="$props.class" />
  <component :is="COMPONENTS[spec.kind]" v-else :class="$props.class" />
</template>
