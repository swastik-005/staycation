/**
 * assets/js/main.js - Interactive frontend controller for Staycation Hotel Management Software
 */

document.addEventListener('DOMContentLoaded', () => {
    // 1. Sticky Nav Header
    const header = document.querySelector('.site-header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header?.classList.add('scrolled');
        } else {
            header?.classList.remove('scrolled');
        }
    });

    // 2. Default Dates (Today + 1, Today + 4)
    const today = new Date();
    const checkInDate = new Date(today);
    checkInDate.setDate(today.getDate() + 1);

    const checkOutDate = new Date(today);
    checkOutDate.setDate(today.getDate() + 4);

    const formatDate = (d) => d.toISOString().split('T')[0];

    const barCheckIn = document.getElementById('bar_check_in');
    const barCheckOut = document.getElementById('bar_check_out');

    if (barCheckIn && barCheckOut) {
        barCheckIn.min = formatDate(today);
        barCheckIn.value = formatDate(checkInDate);
        barCheckOut.min = formatDate(checkInDate);
        barCheckOut.value = formatDate(checkOutDate);

        barCheckIn.addEventListener('change', () => {
            const nextDay = new Date(barCheckIn.value);
            nextDay.setDate(nextDay.getDate() + 1);
            barCheckOut.min = formatDate(nextDay);
            if (barCheckOut.value <= barCheckIn.value) {
                barCheckOut.value = formatDate(nextDay);
            }
        });
    }

    // 3. Quick Booking Bar Filter / Availability Check
    const bookingForm = document.getElementById('quick-booking-form');
    if (bookingForm) {
        bookingForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const checkIn = barCheckIn.value;
            const checkOut = barCheckOut.value;
            const guests = document.getElementById('bar_guests').value;
            const type = document.getElementById('bar_room_type').value;

            const submitBtn = bookingForm.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.innerHTML = '<span>Checking...</span>';
            submitBtn.disabled = true;

            try {
                const res = await fetch(`api/check_availability.php?check_in=${checkIn}&check_out=${checkOut}&guests=${guests}&type=${encodeURIComponent(type)}`);
                const data = await res.json();

                if (data.success) {
                    renderAvailableRooms(data.rooms, checkIn, checkOut, data.nights);
                    const target = document.getElementById('rooms') || document.getElementById('suites');
                    if (target) {
                        target.scrollIntoView({ behavior: 'smooth' });
                    }
                } else {
                    alert(data.error || 'No rooms available for selected dates.');
                }
            } catch (err) {
                console.error(err);
                alert('Error checking suite availability.');
            } finally {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        });
    }

    // Helper to render filtered room cards
    function renderAvailableRooms(rooms, checkIn, checkOut, nights) {
        const grid = document.querySelector('.suites-grid');
        const badge = document.querySelector('#rooms .badge-pill') || document.querySelector('#suites .badge-pill');
        if (badge) {
            badge.innerText = `${rooms.length} Rooms Available for ${nights} Nights (${checkIn} to ${checkOut})`;
        }

        if (!grid) return;

        if (rooms.length === 0) {
            grid.innerHTML = `
                <div style="grid-column: 1/-1; text-align: center; padding: 60px 20px; background: var(--bg-card); border-radius: 16px; border: 1px dashed var(--border-gold);">
                    <h3 style="font-size: 1.8rem; margin-bottom: 12px; color: #fff;">No Rooms Available</h3>
                    <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 24px;">All our rooms are currently reserved for these exact dates. Please select alternative dates or contact us.</p>
                    <button class="btn btn-primary" onclick="document.getElementById('quick-booking-form').scrollIntoView({behavior:'smooth'})">Modify Dates</button>
                </div>
            `;
            return;
        }

        grid.innerHTML = rooms.map(room => `
            <div class="suite-card">
                <div class="suite-img-wrap">
                    <img src="${room.image_url}" alt="${room.title}" loading="lazy">
                    <span class="suite-type-tag">${room.type}</span>
                    <div class="suite-price-badge">
                        <div class="amt">₹${parseFloat(room.price_per_night).toLocaleString('en-IN')}</div>
                        <div class="per">/ Night</div>
                    </div>
                </div>
                <div class="suite-body">
                    <h3>${room.title}</h3>
                    <div class="suite-meta-bar">
                        <span><i class="fa-solid fa-users"></i> Up to ${room.capacity} Guests</span>
                        <span><i class="fa-solid fa-vector-square"></i> ${room.size_sqft} sq ft</span>
                        <span><i class="fa-solid fa-bed"></i> ${room.bed_type}</span>
                    </div>
                    <p class="suite-desc">${room.description}</p>
                    <div class="suite-amenities-tags">
                        ${room.amenities_list.slice(0, 3).map(a => `<span class="amenity-pill">${a}</span>`).join('')}
                        ${room.amenities_list.length > 3 ? `<span class="amenity-pill">+${room.amenities_list.length - 3} more</span>` : ''}
                    </div>
                    <div class="suite-actions">
                        <button class="btn btn-primary btn-sm btn-open-booking" 
                            data-room='${JSON.stringify(room)}'
                            data-checkin="${checkIn}" 
                            data-checkout="${checkOut}">
                            Reserve Room
                        </button>
                        <a href="#contact" class="btn btn-outline btn-sm">Contact Us</a>
                    </div>
                </div>
            </div>
        `).join('');

        attachModalOpenListeners();
    }

    // 4. Booking Modal Logic
    const bookingModal = document.getElementById('booking-modal');
    const modalCloseBtn = document.getElementById('modal-close-btn');

    let currentSelectedRoom = null;

    function attachModalOpenListeners() {
        document.querySelectorAll('.btn-open-booking').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const roomData = JSON.parse(btn.getAttribute('data-room'));
                const checkIn = btn.getAttribute('data-checkin') || barCheckIn?.value;
                const checkOut = btn.getAttribute('data-checkout') || barCheckOut?.value;
                openBookingModal(roomData, checkIn, checkOut);
            });
        });
    }

    attachModalOpenListeners();

    function openBookingModal(room, checkIn, checkOut) {
        currentSelectedRoom = room;

        document.getElementById('modal_room_id').value = room.id;
        document.getElementById('modal_suite_title').innerText = room.title;
        document.getElementById('modal_room_type').innerText = `${room.type} • Suite ${room.room_number}`;
        document.getElementById('modal_room_img').src = room.image_url;
        document.getElementById('modal_price_display').innerText = `₹${parseFloat(room.price_per_night).toLocaleString('en-IN')}`;

        const modalIn = document.getElementById('modal_check_in');
        const modalOut = document.getElementById('modal_check_out');
        const modalGuests = document.getElementById('modal_guests');

        modalIn.min = formatDate(today);
        modalIn.value = checkIn || formatDate(checkInDate);
        modalOut.min = modalIn.value;
        modalOut.value = checkOut || formatDate(checkOutDate);

        // Populate guest capacity options
        modalGuests.innerHTML = '';
        for (let i = 1; i <= room.capacity; i++) {
            const opt = document.createElement('option');
            opt.value = i;
            opt.innerText = `${i} Guest${i > 1 ? 's' : ''}`;
            modalGuests.appendChild(opt);
        }

        recalculateModalSummary();

        bookingModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeBookingModal() {
        bookingModal.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (modalCloseBtn) {
        modalCloseBtn.addEventListener('click', closeBookingModal);
    }

    window.addEventListener('click', (e) => {
        if (e.target === bookingModal) {
            closeBookingModal();
        }
    });

    // Date change in modal
    const modalCheckIn = document.getElementById('modal_check_in');
    const modalCheckOut = document.getElementById('modal_check_out');

    if (modalCheckIn && modalCheckOut) {
        modalCheckIn.addEventListener('change', () => {
            const nextDay = new Date(modalCheckIn.value);
            nextDay.setDate(nextDay.getDate() + 1);
            modalCheckOut.min = formatDate(nextDay);
            if (modalCheckOut.value <= modalCheckIn.value) {
                modalCheckOut.value = formatDate(nextDay);
            }
            recalculateModalSummary();
        });

        modalCheckOut.addEventListener('change', recalculateModalSummary);
    }

    function recalculateModalSummary() {
        if (!currentSelectedRoom) return;

        const inVal = new Date(modalCheckIn.value);
        const outVal = new Date(modalCheckOut.value);
        const nights = Math.max(1, Math.round((outVal - inVal) / (1000 * 60 * 60 * 24)));

        const rate = parseFloat(currentSelectedRoom.price_per_night);
        const subtotal = rate * nights;
        const tax = Math.round(subtotal * 0.12 * 100) / 100;
        const total = subtotal + tax;

        document.getElementById('sum_nights_label').innerText = `₹${rate.toLocaleString('en-IN')} × ${nights} Night${nights > 1 ? 's' : ''}`;
        document.getElementById('sum_subtotal').innerText = `₹${subtotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('sum_tax').innerText = `₹${tax.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
        document.getElementById('sum_total').innerText = `₹${total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
    }

    // 5. Submit Booking Form
    const modalForm = document.getElementById('modal-booking-form');
    if (modalForm) {
        modalForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = modalForm.querySelector('button[type="submit"]');
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span>Securing Reservation...</span>';
            btn.disabled = true;

            const payload = {
                room_id: document.getElementById('modal_room_id').value,
                guest_name: document.getElementById('modal_guest_name').value,
                guest_email: document.getElementById('modal_guest_email').value,
                guest_phone: document.getElementById('modal_guest_phone').value,
                check_in: document.getElementById('modal_check_in').value,
                check_out: document.getElementById('modal_check_out').value,
                num_guests: document.getElementById('modal_guests').value,
                payment_method: document.getElementById('modal_payment_method').value,
                special_requests: document.getElementById('modal_special_requests').value,
            };

            try {
                const res = await fetch('api/book_room.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    closeBookingModal();
                    showConfirmationModal(data.booking);
                } else {
                    alert(data.error || 'Could not complete booking.');
                }
            } catch (err) {
                console.error(err);
                alert('A network error occurred while confirming reservation.');
            } finally {
                btn.innerHTML = originalText;
                btn.disabled = false;
            }
        });
    }

    // 6. Confirmation Voucher Modal
    function showConfirmationModal(booking) {
        const confModal = document.getElementById('confirmation-modal');
        if (!confModal) return;

        document.getElementById('voucher_ref').innerText = booking.booking_reference;
        document.getElementById('voucher_guest').innerText = booking.guest_name;
        document.getElementById('voucher_email').innerText = booking.guest_email;
        document.getElementById('voucher_suite').innerText = `${booking.room_title} (Suite ${booking.room_number})`;
        document.getElementById('voucher_dates').innerText = `${booking.check_in} to ${booking.check_out} (${booking.nights} Nights)`;
        document.getElementById('voucher_guests').innerText = `${booking.num_guests} Guest(s)`;
        document.getElementById('voucher_total').innerText = `₹${parseFloat(booking.total_price).toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
        document.getElementById('voucher_payment').innerText = `${booking.payment_method} • Verified`;

        confModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    const confCloseBtn = document.getElementById('conf-modal-close-btn');
    if (confCloseBtn) {
        confCloseBtn.addEventListener('click', () => {
            document.getElementById('confirmation-modal').classList.remove('active');
            document.body.style.overflow = '';
        });
    }

    // 7. Contact / Inquiry Form Submission
    const contactForm = document.getElementById('contact-form');
    if (contactForm) {
        contactForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = contactForm.querySelector('button[type="submit"]');
            const alertBox = document.getElementById('contact-alert');

            btn.disabled = true;
            btn.innerHTML = 'Sending Inquiry...';

            const payload = {
                name: document.getElementById('contact_name').value,
                email: document.getElementById('contact_email').value,
                phone: document.getElementById('contact_phone').value,
                subject: document.getElementById('contact_subject').value,
                message: document.getElementById('contact_message').value
            };

            try {
                const res = await fetch('api/submit_inquiry.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();

                if (data.success) {
                    alertBox.style.display = 'block';
                    alertBox.className = 'success-box';
                    alertBox.innerHTML = `<strong>Sent!</strong> ${data.message}`;
                    contactForm.reset();
                } else {
                    alertBox.style.display = 'block';
                    alertBox.className = 'error-box';
                    alertBox.innerHTML = `<strong>Error:</strong> ${data.error}`;
                }
            } catch (err) {
                alertBox.style.display = 'block';
                alertBox.className = 'error-box';
                alertBox.innerHTML = 'Network error sending inquiry.';
            } finally {
                btn.disabled = false;
                btn.innerHTML = 'Transmit Inquiry';
            }
        });
    }
});

