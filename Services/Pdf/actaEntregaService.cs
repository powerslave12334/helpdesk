using QuestPDF.Fluent;
using QuestPDF.Helpers;
using QuestPDF.Infrastructure;
using HELPDESK.Models.Inventario.EquipoUsuario;

namespace HELPDESK.Services.Pdf {
    public class actaEntregaService {
        private readonly string _logoPath;

        public actaEntregaService(IWebHostEnvironment env) {
            _logoPath = Path.Combine(env.WebRootPath, "img", "Transp_ALCOMEX.ico");
        }

        public byte[] GenerarActa(equipoUsuarioModel asignacion, string entregadoPor) {
            QuestPDF.Settings.License = LicenseType.Community;

            byte[] logoBytes = File.Exists(_logoPath) ? File.ReadAllBytes(_logoPath) : Array.Empty<byte>();

            byte[] pdfBytes = Document.Create(container => {
                container.Page(page => {
                    page.Size(PageSizes.Letter);
                    page.MarginHorizontal(50);
                    page.MarginVertical(40);

                    page.Header().Element(header => {
                        header.Row(row => {
                            row.RelativeItem(3).AlignCenter().Column(col => {
                                col.Item().Text("ACTA DE ENTREGA DE EQUIPO")
                                    .FontSize(16).Bold().FontColor("#1a1a2e");
                            });

                            row.RelativeItem(1).AlignRight().Column(col => {
                                if (logoBytes.Length > 0) {
                                    col.Item().Width(80).Image(logoBytes);
                                } else {
                                    col.Item().Text("TRANSPORTE LOGISTICA")
                                        .FontSize(10).Bold().FontColor("#1a1a2e");
                                    col.Item().Text("ALCOMEX")
                                        .FontSize(10).Bold().FontColor("#1a1a2e");
                                }
                            });
                        });
                    });

                    page.Content().Element(content => {
                        content.Column(col => {
                            col.Item().PaddingTop(10).Row(row => {
                                row.RelativeItem().Text($"Fecha: {asignacion.FechaAsignacion:dd/MM/yyyy}")
                                    .FontSize(11);
                            });

                            col.Item().PaddingTop(15).Text("Con la presente acta se le hace entrega de lo siguiente:")
                                .FontSize(11);

                            col.Item().PaddingTop(10).Table(table => {
                                table.ColumnsDefinition(columns => {
                                    columns.ConstantColumn(50);
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                    columns.RelativeColumn();
                                });

                                table.Header(header => {
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Cantidad").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Descripcion").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Marca").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Modelo").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("IMEI").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Numero").Bold().FontSize(9).FontColor("#ffffff");
                                    header.Cell().Background("#1a1a2e").Padding(6).Text("Condiciones").Bold().FontSize(9).FontColor("#ffffff");
                                });

                                table.Cell().Background("#f8f9fa").Padding(6).Text("1").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoNombre ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoMarca ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoModelo ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoNumeroSerie ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.EquipoNumeroSerie ?? "N/A").FontSize(10);
                                table.Cell().Background("#f8f9fa").Padding(6).Text(asignacion.Observaciones ?? "N/A").FontSize(10);
                            });

                            col.Item().PaddingTop(20).Column(legal => {
                                legal.Item().Text("El trabajador manifiesta que:").Bold().FontSize(11);
                                legal.Item().PaddingTop(6).Text("El / los equipos que aquí se describe (n), son y serán de la empresa en todo momento, en caso de terminación del contrato de trabajo o entrega de un nuevo equipo, me comprometo a hacer la devolución de forma inmediata. En caso de daño del equipo, el trabajador debera reponer el daño a la empresa.").FontSize(10);
                                legal.Item().PaddingTop(6).Text("Autorizo expresamente a la empresa mediante este documento a descontar de salarios y liquidación de prestaciones los valores de la dotación cuando en cualquiera de los casos anteriores no la devuelve al empleador.").FontSize(10);
                            });

                            col.Item().PaddingTop(40).Row(row => {
                                row.RelativeItem().Column(col => {
                                    col.Item().Text("Entregado por:").Bold().FontSize(11);
                                    col.Item().PaddingTop(50).LineHorizontal(1).LineColor("#1a1a2e");
                                    col.Item().PaddingTop(5).Text($"{entregadoPor.ToUpper()} | SOPORTE TECNICO").FontSize(10).Bold();
                                    col.Item().PaddingTop(15).Text("Nombre, firma y puesto").FontSize(9).FontColor("#666666");
                                });

                                row.RelativeItem().Column(col => {
                                    col.Item().Text("Recibido por:").Bold().FontSize(11);
                                    col.Item().PaddingTop(50).LineHorizontal(1).LineColor("#1a1a2e");
                                    col.Item().PaddingTop(5).Text($"{asignacion.NombreUsuario.ToUpper()} | CUENTAS POR COBRAR").FontSize(10).Bold();
                                    col.Item().PaddingTop(15).Text("Nombre, firma y puesto").FontSize(9).FontColor("#666666");
                                });
                            });
                        });
                    });
                });
            }).GeneratePdf();

            return pdfBytes;
        }
    }
}
