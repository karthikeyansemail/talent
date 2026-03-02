import { config } from './config.mjs';
import { mkdirSync, readFileSync, writeFileSync, existsSync } from 'fs';
import { resolve } from 'path';

// Ensure .bridge directory exists
mkdirSync(config.paths.bridge, { recursive: true });
mkdirSync(config.paths.responses, { recursive: true });

const DB_PATH = resolve(config.paths.bridge, 'store.json');

/**
 * Simple JSON file-based store.
 * No native compilation needed — pure JavaScript.
 */
function loadStore() {
    if (existsSync(DB_PATH)) {
        try {
            return JSON.parse(readFileSync(DB_PATH, 'utf-8'));
        } catch {
            return createEmpty();
        }
    }
    return createEmpty();
}

function createEmpty() {
    return { events: [], testRuns: [], instructions: [], nextInstructionId: 1, settings: { approvalMode: 'notify' } };
}

function saveStore(store) {
    writeFileSync(DB_PATH, JSON.stringify(store, null, 2));
}

let store = loadStore();

function now() {
    return new Date().toISOString();
}

// Query helpers — same interface as the old SQLite version
export const queries = {
    insertEvent(id, type, summary, data) {
        store.events.push({
            id, type, summary,
            data: typeof data === 'string' ? data : JSON.stringify(data),
            status: 'pending',
            response: null,
            telegram_message_id: null,
            created_at: now(),
            responded_at: null,
        });
        saveStore(store);
    },

    getEvent(id) {
        return store.events.find(e => e.id === id) || null;
    },

    updateEventResponse(id, status, response, telegramMessageId) {
        const evt = store.events.find(e => e.id === id);
        if (evt) {
            evt.status = status;
            evt.response = response;
            evt.telegram_message_id = telegramMessageId;
            evt.responded_at = now();
            saveStore(store);
        }
    },

    getPendingEvents() {
        return store.events
            .filter(e => e.status === 'pending')
            .sort((a, b) => b.created_at.localeCompare(a.created_at))
            .slice(0, 20);
    },

    getRecentEvents(limit = 10) {
        return store.events
            .sort((a, b) => b.created_at.localeCompare(a.created_at))
            .slice(0, limit);
    },

    insertTestRun(id, suite) {
        store.testRuns.push({
            id, suite,
            status: 'running',
            results: null,
            total: 0, passed: 0, failed: 0,
            started_at: now(),
            finished_at: null,
        });
        saveStore(store);
    },

    updateTestRun(id, status, results, total, passed, failed) {
        const run = store.testRuns.find(r => r.id === id);
        if (run) {
            run.status = status;
            run.results = typeof results === 'string' ? results : JSON.stringify(results);
            run.total = total;
            run.passed = passed;
            run.failed = failed;
            run.finished_at = now();
            saveStore(store);
        }
    },

    getTestRun(id) {
        return store.testRuns.find(r => r.id === id) || null;
    },

    getLastTestRun() {
        if (store.testRuns.length === 0) return null;
        return store.testRuns
            .sort((a, b) => b.started_at.localeCompare(a.started_at))[0];
    },

    insertInstruction(text, source = 'telegram', mode = 'plan') {
        store.instructions.push({
            id: store.nextInstructionId++,
            text, source, mode,
            consumed: 0,
            created_at: now(),
        });
        saveStore(store);
    },

    getUnconsumedInstructions() {
        return store.instructions
            .filter(i => i.consumed === 0)
            .sort((a, b) => a.created_at.localeCompare(b.created_at));
    },

    markInstructionConsumed(id) {
        const instr = store.instructions.find(i => i.id === id);
        if (instr) {
            instr.consumed = 1;
            saveStore(store);
        }
    },

    getApprovalMode() {
        if (!store.settings) store.settings = { approvalMode: 'notify' };
        return store.settings.approvalMode || 'notify';
    },

    setApprovalMode(mode) {
        if (!store.settings) store.settings = {};
        store.settings.approvalMode = mode;
        saveStore(store);
    },
};

export default store;
