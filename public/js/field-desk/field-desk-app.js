function fieldDeskApp() {
    const cfg = window.fieldDeskConfig || {};
    return {
                loading: false,
                getCsrfToken() { return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || (window.fieldDeskConfig?.csrfToken || ''); },
                timeline: 'all_active',
                categoryId: '',
                mruId: '',
                priority: '',
                search: cfg.initialCa || '',
                page: 1,
                items: [],
                counts: Object.assign({
                    due_today: 0,
                    overdue: 0,
                    upcoming: 0,
                    resolved_this_month: 0,
                    total_active: 0
                }, cfg.counts || {}),
                pagination: {
                    current_page: 1,
                    last_page: 1,
                    per_page: 25,
                    total: 0
                },
                modals: {
                    create: false,
                    complete: false,
                    timeline: false,
                    edit: false,
                    quickGps: false,
                    quickMobile: false
                },
                gpsLoading: false,
                form: {
                    ca_number: '',
                    category_id: String(cfg.defaultCategoryId || '1'),
                    target_date: new Date().toISOString().split('T')[0],
                    priority: 'normal',
                    mru_id: '',
                    target_amount: '',
                    payment_mode: '',
                    private_note: '',
                    mobile: '',
                    latitude: '',
                    longitude: '',
                    location_accuracy: null,
                    save_to_consumer: true
                },
                editForm: {
                    id: null,
                    category_id: '',
                    target_date: '',
                    priority: 'normal',
                    target_amount: '',
                    payment_mode: '',
                    private_note: '',
                    mobile: '',
                    latitude: '',
                    longitude: '',
                    location_accuracy: null,
                    save_to_consumer: true
                },
                quickGpsForm: {
                    ca_number: '',
                    latitude: '',
                    longitude: '',
                    location_accuracy: null
                },
                quickMobileForm: {
                    ca_number: '',
                    mobile: ''
                },
                completeForm: {
                    id: null,
                    collected_amount: '',
                    note: ''
                },
                activeAction: null,
                timelineActivities: [],
                toast: {
                    show: false,
                    message: '',
                    icon: '✅'
                },

                async initApp() {
                    await this.fetchData();
                    // If initial CA passed via deep link, open create modal if not found
                    if (cfg.initialCa) {
                        if (this.items.length === 0) {
                            this.openCreateModal(cfg.initialCa);
                        }
                    }
                },

                setTimeline(t) {
                    this.timeline = t;
                    this.page = 1;
                    this.fetchData();
                },

                goToPage(p) {
                    this.page = p;
                    this.fetchData();
                },

                async fetchData() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams({
                            timeline: this.timeline,
                            category_id: this.categoryId,
                            mru_id: this.mruId,
                            priority: this.priority,
                            search: this.search,
                            page: this.page,
                            per_page: 25
                        });
                        const res = await fetch(`` + (cfg.apiDataUrl || '/api/field-desk/data') + `?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.items = json.data;
                            this.counts = json.counts;
                            this.pagination = json.pagination;
                        }
                    } catch (e) {
                        this.showToast('Failed to load data', '❌');
                    } finally {
                        this.loading = false;
                    }
                },

                setDatePreset(days) {
                    const d = new Date();
                    d.setDate(d.getDate() + days);
                    this.form.target_date = d.toISOString().split('T')[0];
                },

                openCreateModal(ca = '') {
                    this.form.ca_number = ca || this.search || '';
                    this.form.target_date = new Date().toISOString().split('T')[0];
                    this.form.target_amount = '';
                    this.form.payment_mode = '';
                    this.form.private_note = '';
                    this.form.mobile = '';
                    this.form.latitude = '';
                    this.form.longitude = '';
                    this.form.location_accuracy = null;
                    this.form.save_to_consumer = true;
                    this.modals.create = true;
                },

                captureGpsLocation(mode = 'create') {
                    if (!navigator.geolocation) {
                        this.showToast('Geolocation is not supported by your browser', '❌');
                        return;
                    }
                    this.gpsLoading = true;
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = Number(position.coords.latitude.toFixed(8));
                            const lng = Number(position.coords.longitude.toFixed(8));
                            const acc = position.coords.accuracy ? Number(position.coords.accuracy.toFixed(1)) : null;

                            if (mode === 'create') {
                                this.form.latitude = lat;
                                this.form.longitude = lng;
                                this.form.location_accuracy = acc;
                            } else if (mode === 'edit') {
                                this.editForm.latitude = lat;
                                this.editForm.longitude = lng;
                                this.editForm.location_accuracy = acc;
                            } else if (mode === 'quick') {
                                this.quickGpsForm.latitude = lat;
                                this.quickGpsForm.longitude = lng;
                                this.quickGpsForm.location_accuracy = acc;
                            }

                            this.gpsLoading = false;
                            const precisionMsg = acc && acc <= 10 ? `🎯 Locked! Sub-10m precision (±${Math.round(acc)}m)` : `📍 Position captured (±${Math.round(acc || 0)}m)`;
                            this.showToast(precisionMsg, '🟢');
                        },
                        (error) => {
                            this.gpsLoading = false;
                            let msg = 'Failed to get location';
                            if (error.code === 1) msg = 'Location permission denied by user';
                            else if (error.code === 2) msg = 'Position unavailable. Check device GPS';
                            else if (error.code === 3) msg = 'GPS timeout. Stand under open sky';
                            this.showToast(msg, '⚠️');
                        },
                        {
                            enableHighAccuracy: true,
                            timeout: 15000,
                            maximumAge: 0
                        }
                    );
                },

                openQuickGpsModal(item) {
                    this.quickGpsForm = {
                        ca_number: item.ca_number,
                        latitude: item.latitude || item.consumer_latitude || '',
                        longitude: item.longitude || item.consumer_longitude || '',
                        location_accuracy: item.location_accuracy || item.consumer_accuracy || null
                    };
                    this.modals.quickGps = true;
                },

                async submitQuickGps() {
                    if (!this.quickGpsForm.latitude || !this.quickGpsForm.longitude) {
                        this.showToast('Please capture or enter coordinates first', '⚠️');
                        return;
                    }
                    try {
                        const res = await fetch(`/api/field-desk/consumer/${this.quickGpsForm.ca_number}/contact`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                latitude: this.quickGpsForm.latitude,
                                longitude: this.quickGpsForm.longitude,
                                location_accuracy: this.quickGpsForm.location_accuracy
                            })
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.modals.quickGps = false;
                            this.showToast('GPS coordinates saved to Consumer! 📍', '✅');
                            this.fetchData();
                        } else {
                            this.showToast(json.message || 'Failed to save coordinates', '⚠️');
                        }
                    } catch (e) {
                        this.showToast('Error saving GPS coordinates', '❌');
                    }
                },

                openQuickMobileModal(item) {
                    this.quickMobileForm = {
                        ca_number: item.ca_number,
                        mobile: item.consumer_mobile || ''
                    };
                    this.modals.quickMobile = true;
                },

                async submitQuickMobile() {
                    if (!this.quickMobileForm.mobile || this.quickMobileForm.mobile.trim().length !== 10) {
                        this.showToast('Please enter a valid 10-digit mobile number', '⚠️');
                        return;
                    }
                    try {
                        const res = await fetch(`/api/field-desk/consumer/${this.quickMobileForm.ca_number}/contact`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                mobile: this.quickMobileForm.mobile.trim()
                            })
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.modals.quickMobile = false;
                            this.showToast('Mobile number saved to Consumer! 📱', '✅');
                            this.fetchData();
                        } else {
                            this.showToast(json.message || 'Failed to save mobile', '⚠️');
                        }
                    } catch (e) {
                        this.showToast('Error saving mobile number', '❌');
                    }
                },

                async submitCreate() {
                    try {
                        const res = await fetch(cfg.apiStoreUrl || '/api/field-desk/store', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.form)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.modals.create = false;
                            this.showToast('Action created successfully in FieldDesk', '⚡');
                            this.fetchData();
                        } else {
                            this.showToast(json.message || 'Validation error', '⚠️');
                        }
                    } catch (e) {
                        this.showToast('Error saving action', '❌');
                    }
                },

                openEditModal(item) {
                    this.editForm = {
                        id: item.id,
                        category_id: item.category_id,
                        target_date: item.target_date,
                        priority: item.priority,
                        target_amount: item.target_amount || '',
                        payment_mode: item.payment_mode || '',
                        private_note: item.private_note || '',
                        mobile: item.consumer_mobile || '',
                        latitude: item.latitude || item.consumer_latitude || '',
                        longitude: item.longitude || item.consumer_longitude || '',
                        location_accuracy: item.location_accuracy || item.consumer_accuracy || null,
                        save_to_consumer: true
                    };
                    this.modals.edit = true;
                },

                async submitEdit() {
                    try {
                        const res = await fetch(`/api/field-desk/actions/${this.editForm.id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.editForm)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.modals.edit = false;
                            this.showToast('Action updated successfully', '✅');
                            this.fetchData();
                        } else {
                            this.showToast(json.message || 'Validation error', '⚠️');
                        }
                    } catch (e) {
                        this.showToast('Error updating action', '❌');
                    }
                },

                async quickReschedule(id, days) {
                    try {
                        const res = await fetch(`/api/field-desk/actions/${id}/reschedule`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ days: days })
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.showToast(`Snoozed +${days} days!`, '🔄');
                            this.fetchData();
                        }
                    } catch (e) {
                        this.showToast('Failed to snooze action', '❌');
                    }
                },

                openCompleteModal(item) {
                    this.activeAction = item;
                    this.completeForm = {
                        id: item.id,
                        collected_amount: item.target_amount > 0 ? (item.remaining_amount || item.target_amount) : '',
                        note: ''
                    };
                    this.modals.complete = true;
                },

                async submitComplete() {
                    try {
                        const res = await fetch(`/api/field-desk/actions/${this.completeForm.id}/complete`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.completeForm)
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.modals.complete = false;
                            this.showToast('Marked as completed / resolved! 🎉', '✅');
                            this.fetchData();
                        }
                    } catch (e) {
                        this.showToast('Error completing action', '❌');
                    }
                },

                async openTimelineDrawer(id) {
                    this.timelineActivities = [];
                    this.modals.timeline = true;
                    try {
                        const res = await fetch(`/api/field-desk/actions/${id}`, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.timelineActivities = json.activities || [];
                        }
                    } catch (e) {
                        this.showToast('Could not load history', '❌');
                    }
                },

                async logCommunication(id, type, note) {
                    try {
                        await fetch(`/api/field-desk/actions/${id}/activity`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ action_type: type, note: note })
                        });
                    } catch (e) {}
                },

                async deleteAction(id) {
                    if (!confirm('Are you sure you want to remove this action?')) return;
                    try {
                        const res = await fetch(`/api/field-desk/actions/${id}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': this.getCsrfToken(),
                                'Accept': 'application/json'
                            }
                        });
                        const json = await res.json();
                        if (json.success) {
                            this.showToast('Action removed', '🗑️');
                            this.fetchData();
                        }
                    } catch (e) {
                        this.showToast('Error removing action', '❌');
                    }
                },

                copyText(text, msg) {
                    navigator.clipboard.writeText(text);
                    this.showToast(msg, '📋');
                },

                showToast(msg, icon = '✅') {
                    this.toast.message = msg;
                    this.toast.icon = icon;
                    this.toast.show = true;
                    setTimeout(() => {
                        this.toast.show = false;
                    }, 3000);
                }
            };
        }