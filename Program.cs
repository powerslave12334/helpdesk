using HELPDESK.Services;
using HELPDESK.Services.Pdf;
using HELPDESK.Hubs;

var builder = WebApplication.CreateBuilder(args);

builder.Services.AddControllersWithViews();
builder.Services.AddDistributedMemoryCache();
builder.Services.AddSession(options => {
    options.IdleTimeout = TimeSpan.FromMinutes(30);
    options.Cookie.HttpOnly = true;
    options.Cookie.IsEssential = true;
});
builder.Services.AddSignalR();
builder.Services.AddScoped<DBConector>();
builder.Services.AddScoped<PermisosService>();
builder.Services.AddScoped<CorreoService>();
builder.Services.AddScoped<NotificacionService>();
builder.Services.AddScoped<actaEntregaService>();

var app = builder.Build();

if (!app.Environment.IsDevelopment()) {
    app.UseExceptionHandler("/Home/Error");
    app.UseHsts();
}

app.UseStaticFiles();
app.UseRouting();
app.UseSession();
app.UseAuthorization();

app.MapControllerRoute(
    name: "default",
    pattern: "{controller=Login}/{action=Login}/{id?}");
app.MapHub<NotificationHub>("/notificationHub");

app.Run();
