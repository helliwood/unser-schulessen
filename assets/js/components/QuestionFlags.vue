<template>
    <span>
        <i v-for="flag in visibleFlags" 
           :key="flag.name"
           :class="flag.icon + ' ml-1'" 
           :title="flag.description" 
           :style="flag.color ? 'color: ' + flag.color + ';' : ''"
           data-toggle="tooltip" 
           data-placement="top">
        </i>
    </span>
</template>

<script>
export default {
    name: 'QuestionFlags',
    props: {
        question: {
            type: Object,
            required: true
        }
    },
    computed: {
        visibleFlags() {
            if (!this.question || !this.question.flags || !window.flagDefinitions) {
                return [];
            }
            
            const flags = [];
            for (const [flagName, definition] of Object.entries(window.flagDefinitions)) {
                if (this.question.flags[flagName] === true) {
                    flags.push({
                        name: flagName,
                        ...definition
                    });
                }
            }
            return flags;
        }
    }
}
</script> 