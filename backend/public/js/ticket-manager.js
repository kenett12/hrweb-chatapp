/**
 * Global Ticket Management System
 * Handles ticket creation, management, and real-time updates
 */

class TicketManager {
  constructor() {
    this.tickets = [];
    this.currentFilter = "all";
    this.socket = null;
    this.userId = null;
    this.userRole = null;
    this.isSubmitting = false;
    this.socketRetryCount = 0;

    this.init();
  }

  init() {
    this.userId = window.userId || document.querySelector('meta[name="user-id"]')?.content;
    this.userRole = window.userRole || document.querySelector('meta[name="user-role"]')?.content;
    
    console.log("Initializing TicketManager for user:", this.userId);

    this.initializeSocket();
    this.setupEventListeners();
    this.loadTickets();
    this.updateTicketStats();
    
    // Start the visibility enforcer immediately
    this.startVisibilityEnforcer();
  }

  initializeSocket() {
    if (window.socket && window.socket.connected) {
      this.socket = window.socket;
      console.log("✅ Socket connected for ticket manager");
      this.attachSocketListeners();
    } else if (this.socketRetryCount < 10) {
      this.socketRetryCount++;
      setTimeout(() => this.initializeSocket(), 500);
    }
  }

  attachSocketListeners() {
    this.socket.on("new_ticket", (ticket) => this.handleNewTicket(ticket));
    this.socket.on("ticket_updated", (ticket) => this.handleTicketUpdate(ticket));
    this.socket.on("ticket_status_changed", (data) => this.handleTicketUpdate(data.ticket));
  }

  setupEventListeners() {
    // Filter Buttons
    document.addEventListener("click", (e) => {
      if (e.target.matches(".filter-btn")) {
        this.handleFilterClick(e.target);
      }
    });

    // Open Modal
    const createTicketBtn = document.getElementById("create-ticket-btn");
    if (createTicketBtn) {
      createTicketBtn.addEventListener("click", (e) => {
        e.preventDefault();
        this.openCreateTicketModal();
      });
    }

    // Close Modal
    const closeSelectors = ["#close-create-ticket-modal", "#cancel-create-ticket", "#create-ticket-backdrop", ".close-overlay"];
    closeSelectors.forEach(selector => {
        document.querySelectorAll(selector).forEach(el => {
            el.addEventListener("click", (e) => {
                if (e.target === el || selector !== "#create-ticket-backdrop") {
                    e.preventDefault();
                    this.closeCreateTicketModal();
                }
            });
        });
    });

    // Submit Form
    const createTicketForm = document.getElementById("create-ticket-form");
    if (createTicketForm) {
      createTicketForm.addEventListener("submit", (e) => {
        e.preventDefault();
        this.submitTicket(e.target);
      });
    }
  }

  // Active Tab Visibility Enforcer
  startVisibilityEnforcer() {
    setInterval(() => {
        const ticketList = document.getElementById("my-tickets-list");
        if (!ticketList) return;

        const ticketSection = ticketList.closest('.content-section');
        if (!ticketSection) return;

        const activeTabBtn = document.querySelector('.nav-rail-btn.active');
        const activeTabName = activeTabBtn ? activeTabBtn.getAttribute('data-tab') : '';

        if (activeTabName === 'ticketing') {
            if (ticketSection.style.display === 'none') {
                ticketSection.style.removeProperty('display');
            }
        } else {
            ticketSection.style.display = 'none';
        }
    }, 100);
  }

  loadTickets() {
    const myTicketsList = document.getElementById("my-tickets-list");
    if (!myTicketsList) return;

    const base = window.baseUrl || "/";
    const url = `${base.replace(/\/$/, '')}/api/tickets/user`;
    
    fetch(url, {
        headers: { "X-Requested-With": "XMLHttpRequest" }
    })
    .then(res => res.json())
    .then(data => {
        this.tickets = Array.isArray(data) ? data : [];
        this.renderTickets();
        this.updateTicketStats();
    })
    .catch(err => console.error("Error loading tickets:", err));
  }

  openCreateTicketModal() {
    const modal = document.getElementById("create-ticket-modal");
    if (modal) {
      modal.classList.remove("hidden");
      modal.style.display = "flex"; 
      document.body.style.overflow = "hidden";
    }
  }

  closeCreateTicketModal() {
    const modal = document.getElementById("create-ticket-modal");
    if (modal) {
      modal.classList.add("hidden");
      modal.style.removeProperty("display");
      document.body.style.overflow = "";
      const form = document.getElementById("create-ticket-form");
      if (form) form.reset();
    }
  }

  async submitTicket(form) {
    if (this.isSubmitting) return;
    this.isSubmitting = true;

    const submitBtn = form.querySelector('button[type="submit"]');
    const originalHtml = submitBtn ? submitBtn.innerHTML : "Create Ticket";
    
    if (submitBtn) {
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
      submitBtn.disabled = true;
    }

    try {
      const formData = new FormData(form);
      const data = Object.fromEntries(formData.entries());

      const base = window.baseUrl || "/";
      const url = `${base.replace(/\/$/, '')}/api/tickets/create`; 
      
      const response = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-Requested-With": "XMLHttpRequest",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]')?.content
        },
        body: JSON.stringify(data),
      });

      const result = await response.json();

      if (response.ok && result.success) {
        if(result.ticket) result.ticket.customer_name = "Me"; 
        
        this.tickets.unshift(result.ticket); 
        this.renderTickets(); 
        this.updateTicketStats();
        this.closeCreateTicketModal();
        this.showNotification("Ticket created successfully!", "success");
        
        if (this.socket) {
             this.socket.emit("ticket_created", result.ticket);
        }
      } else {
        const errorMsg = result.error || (result.messages ? JSON.stringify(result.messages) : "Failed to create ticket");
        this.showNotification(errorMsg, "error");
      }
    } catch (error) {
      console.error("Submission error:", error);
      this.showNotification("Network error. Please try again.", "error");
    } finally {
      this.isSubmitting = false;
      if (submitBtn) {
        submitBtn.innerHTML = originalHtml;
        submitBtn.disabled = false;
      }
    }
  }

  renderTickets() {
    const myTicketsList = document.getElementById("my-tickets-list");
    if (!myTicketsList) return;

    const filteredTickets = this.getFilteredTickets();
    myTicketsList.innerHTML = "";

    if (filteredTickets.length === 0) {
      this.renderEmptyState(
        this.currentFilter === 'all' ? "No Tickets" : "No tickets found", 
        this.currentFilter === 'all' ? "You haven't created any tickets yet." : `No tickets with status: ${this.currentFilter}`
      );
      return;
    }

    filteredTickets.forEach((ticket, index) => {
      const ticketElement = this.createTicketElement(ticket);
      ticketElement.style.opacity = "0";
      ticketElement.style.transform = "translateY(10px)";
      myTicketsList.appendChild(ticketElement);
      
      setTimeout(() => {
        ticketElement.style.transition = "all 0.3s ease";
        ticketElement.style.opacity = "1";
        ticketElement.style.transform = "translateY(0)";
      }, 50 * index);
    });

    this.updateTicketCount(filteredTickets.length);
  }

  createTicketElement(ticket) {
    const ticketDiv = document.createElement("div");
    ticketDiv.className = "ticket-item"; 
    
    // Fallback style check
    if (!document.querySelector('style').innerHTML.includes('.ticket-item')) {
        ticketDiv.className = "conversation-item";
    }
    
    ticketDiv.dataset.ticketId = ticket.id;
    ticketDiv.dataset.status = ticket.status; 

    const timeAgo = this.getTimeAgo(ticket.updated_at || ticket.created_at);
    
    // HTML Structure
    ticketDiv.innerHTML = `
      <div class="conversation-info" style="width: 100%;">
        <div style="display:flex; justify-content:space-between; align-items:flex-start;">
            <div class="conversation-name">${this.escapeHtml(ticket.subject)}</div>
            <div style="font-size: 10px; color: var(--text-tertiary);">#${ticket.id}</div>
        </div>
        
        <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
          <span class="conversation-preview" style="color: ${this.getStatusColor(ticket.status)}">
            ${ticket.status.replace("-", " ").toUpperCase()}
          </span>
          <span class="conversation-time">${timeAgo}</span>
        </div>
      </div>
    `;
    
    // This listener was calling a missing function. The function is now added below.
    ticketDiv.addEventListener("click", () => this.openTicketDetails(ticket.id));
    return ticketDiv;
  } 

  // === FIXED: ADDED MISSING FUNCTION ===
 openTicketDetails(ticketId) {
  console.log("Opening ticket details:", ticketId);

  // 1. Visual Feedback: Highlight active item
  const allTickets = document.querySelectorAll('.ticket-item');
  allTickets.forEach(el => el.classList.remove('active'));
  
  const currentTicket = document.querySelector(`.ticket-item[data-ticket-id="${ticketId}"]`);
  if (currentTicket) currentTicket.classList.add('active');

  // 2. Redirect to the ticket view
  // This will open your backend\app\Views\tickets\index.php via the controller
  window.location.href = `${window.baseUrl}tickets/index/${ticketId}`;
}

  getStatusColor(status) {
      switch(status) {
          case 'open': return 'var(--success)';
          case 'in-progress': return 'var(--warning)';
          case 'closed': return 'var(--text-muted)';
          default: return 'var(--primary)';
      }
  }

  getFilteredTickets() {
    if (this.currentFilter === "all") return this.tickets;
    return this.tickets.filter(t => t.status === this.currentFilter);
  }

  handleFilterClick(filterBtn) {
    document.querySelectorAll(".filter-btn").forEach(btn => btn.classList.remove("active"));
    filterBtn.classList.add("active");
    this.currentFilter = filterBtn.dataset.filter || "all";
    this.renderTickets();
  }

  updateTicketStats() {
    const myTicketsCount = document.getElementById("my-tickets-count");
    if (myTicketsCount) myTicketsCount.textContent = this.tickets.length;
  }
  
  updateTicketCount(count) {
     const el = document.getElementById("my-tickets-count");
     if(el) el.textContent = count;
  }

  renderEmptyState(title = "No Tickets", message = "No tickets found.") {
    const list = document.getElementById("my-tickets-list");
    if (list) {
      list.innerHTML = `
        <div class="empty-state large">
            <div class="empty-icon large">
                <i class="fas fa-ticket-alt"></i>
            </div>
            <h4>${title}</h4>
            <p>${message}</p>
        </div>`;
    }
  }

  handleNewTicket(ticket) {
    if (ticket.created_by == this.userId) {
      if (!this.tickets.find(t => t.id == ticket.id)) {
          this.tickets.unshift(ticket);
          this.renderTickets();
          this.updateTicketStats();
      }
    }
  }

  handleTicketUpdate(ticket) {
    const index = this.tickets.findIndex((t) => t.id === ticket.id);
    if (index !== -1) {
      this.tickets[index] = { ...this.tickets[index], ...ticket };
      this.renderTickets();
    }
  }

  getTimeAgo(timestamp) {
    if (!timestamp) return "Just now";
    const date = new Date(timestamp);
    const diff = Math.floor((new Date() - date) / 1000);
    
    if (diff < 60) return "Just now";
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
  }

  escapeHtml(text) {
    if (!text) return "";
    const div = document.createElement("div");
    div.textContent = text;
    return div.innerHTML;
  }

  showNotification(message, type = "info") {
    let container = document.getElementById("toast-container");
    if (!container) {
        container = document.createElement("div");
        container.id = "toast-container";
        container.style.position = 'fixed';
        container.style.top = '20px';
        container.style.right = '20px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }

    const toast = document.createElement("div");
    const bg = type === 'success' ? '#10b981' : (type === 'error' ? '#ef4444' : '#3b82f6');
    
    toast.style.cssText = `background:${bg}; color:white; padding:12px 20px; margin-bottom:10px; border-radius:8px; display:flex; align-items:center; box-shadow:0 4px 6px rgba(0,0,0,0.1); animation: slideIn 0.3s ease;`;
    toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-info-circle'}" style="margin-right:10px"></i> ${message}`;

    container.appendChild(toast);
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
  }
}

document.addEventListener("DOMContentLoaded", () => {
  window.ticketManager = new TicketManager();
});