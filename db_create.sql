CREATE DATABASE [feedcache]
GO
USE [feedcache]
GO
/****** Object:  Table [dbo].[cache_data]    Script Date: 12/1/2016 9:06:19 AM ******/
SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
SET ANSI_PADDING ON
GO
CREATE TABLE [dbo].[cache_data](
	[id] [varchar](50) NOT NULL,
	[last_run] [bigint] NOT NULL,
	[cache_content] [nvarchar](max) NOT NULL
) ON [PRIMARY] TEXTIMAGE_ON [PRIMARY]

GO
SET ANSI_PADDING OFF
GO
