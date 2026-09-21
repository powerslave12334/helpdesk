using Microsoft.AspNetCore.SignalR;

namespace HELPDESK.Hubs {
    public class NotificationHub : Hub {
        public async Task JoinGroup(string userId) {
            await Groups.AddToGroupAsync(Context.ConnectionId, $"user_{userId}");
        }

        public async Task LeaveGroup(string userId) {
            await Groups.RemoveFromGroupAsync(Context.ConnectionId, $"user_{userId}");
        }
    }
}
