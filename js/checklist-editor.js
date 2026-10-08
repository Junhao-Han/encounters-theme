(function () {
    'use strict';

    function parse(html) {
        const body = new DOMParser().parseFromString(html || '', 'text/html').body;
        const items = [];
        body.querySelectorAll('ul, ol').forEach((list) => {
            if (list.closest('li')) return;
            Array.from(list.children).forEach((item) => {
                if (item.tagName === 'LI') items.push(item.innerHTML);
            });
            list.remove();
        });
        return {introduction: body.innerHTML, items};
    }

    function hasText(html) {
        return new DOMParser().parseFromString(html, 'text/html').body.textContent.trim() !== '';
    }

    pkp.registry.registerComponent('EncountersChecklistEditor', {
        extends: pkp.registry.getComponent('PkpFieldBase'),
        props: {editorLabels: Object, toolbar: String, plugins: Array},
        data() {
            return {introduction: '', items: [], nextId: 0, revision: 0, lastValue: null, removed: null};
        },
        watch: {
            currentValue: {
                immediate: true,
                handler(value) {
                    // Parent updates after typing must not recreate the active editors.
                    if (value === this.lastValue) return;
                    this.lastValue = value;
                    const checklist = parse(value);
                    this.introduction = checklist.introduction;
                    this.items = checklist.items.map((html) => ({id: ++this.nextId, html}));
                    this.removed = null;
                    this.revision++;
                },
            },
        },
        methods: {
            requirementLabel(index) {
                return this.editorLabels.requirement.replace('{$number}', index + 1);
            },
            editorName(id) {
                return this.localizedName + '-' + id;
            },
            updateIntroduction(name, prop, html) {
                if (prop !== 'value' || html === this.introduction) return;
                this.introduction = html;
                this.publish();
            },
            updateItem(item, prop, html) {
                if (prop !== 'value' || item.html === html) return;
                item.html = html;
                this.publish();
            },
            publish() {
                const items = this.items.filter((item) => hasText(item.html));
                this.lastValue = this.introduction + (items.length
                    ? '<ul>' + items.map((item) => '<li>' + item.html + '</li>').join('') + '</ul>'
                    : '');
                this.currentValue = this.lastValue;
            },
            add() {
                const item = {id: ++this.nextId, html: ''};
                this.items.push(item);
                this.focusRow(item.id);
            },
            move(index, offset) {
                const target = index + offset;
                if (target < 0 || target >= this.items.length) return;
                const [item] = this.items.splice(index, 1);
                this.items.splice(target, 0, item);
                // Moving an iframe in the DOM unloads it; remount the row editors.
                this.revision++;
                this.publish();
                this.focusRow(item.id);
            },
            remove(index) {
                this.removed = {item: this.items[index], index};
                this.items.splice(index, 1);
                this.publish();
                this.$nextTick(() => this.$refs.undo?.$el.focus());
            },
            undo() {
                const {item, index} = this.removed;
                this.items.splice(Math.min(index, this.items.length), 0, item);
                this.removed = null;
                this.publish();
                this.focusRow(item.id);
            },
            focusRow(id) {
                this.$nextTick(() => this.$el.querySelector('[data-row-id="' + id + '"]')?.focus());
            },
        },
        template: `
            <fieldset class="pkpFormField encounters-checklist-editor" :aria-describedby="describedByDescriptionId">
                <legend class="pkpFormFieldLabel">{{ label }} <span v-if="localeLabel">({{ localeLabel }})</span></legend>
                <p :id="describedByDescriptionId" class="pkpFormField__description">{{ description }}</p>
                <pkp-field-rich-textarea
                    :name="editorName('introduction')" :form-id="formId" :label="editorLabels.introduction"
                    :value="introduction" :toolbar="toolbar" :plugins="plugins"
                    @change="updateIntroduction"
                />
                <p v-if="!items.length" class="pkpFormField__description">{{ editorLabels.empty }}</p>
                <div v-for="(item, index) in items" :key="item.id + '-' + revision"
                    class="encounters-checklist-editor__item" :data-row-id="item.id" tabindex="-1"
                    role="group" :aria-label="requirementLabel(index)">
                    <pkp-field-rich-textarea
                        :name="editorName(item.id)" :form-id="formId" :label="requirementLabel(index)"
                        :value="item.html" :toolbar="toolbar" :plugins="plugins"
                        @change="(name, prop, html) => updateItem(item, prop, html)"
                    />
                    <div class="encounters-checklist-editor__actions">
                        <pkp-button :disabled="index === 0" :aria-label="editorLabels.up + ': ' + requirementLabel(index)" @click="move(index, -1)">{{ editorLabels.up }}</pkp-button>
                        <pkp-button :disabled="index === items.length - 1" :aria-label="editorLabels.down + ': ' + requirementLabel(index)" @click="move(index, 1)">{{ editorLabels.down }}</pkp-button>
                        <pkp-button :aria-label="editorLabels.remove + ': ' + requirementLabel(index)" @click="remove(index)">{{ editorLabels.remove }}</pkp-button>
                    </div>
                </div>
                <div class="encounters-checklist-editor__actions">
                    <pkp-button @click="add">{{ editorLabels.add }}</pkp-button>
                    <pkp-button v-if="removed" ref="undo" @click="undo">{{ editorLabels.undo }}</pkp-button>
                </div>
                <p class="pkpFormField__description">{{ editorLabels.blank }}</p>
                <field-error v-if="errors.length" :id="describedByErrorId" :messages="errors" />
            </fieldset>`,
    });
}());
